<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

use Scott\Payment\Sdk\Api\OpenApiEndpoint;
use Scott\Payment\Sdk\Auth\MerchantJwtSigner;
use Scott\Payment\Sdk\Config\MerchantConfigLoader;
use Scott\Payment\Sdk\Crypto\OpenApiPayloadCrypto;
use Scott\Payment\Sdk\Exception\OpenApiHttpException;
use Scott\Payment\Sdk\Exception\OpenApiResponseException;
use Scott\Payment\Sdk\Exception\OpenApiValidationException;
use Scott\Payment\Sdk\Http\CurlHttpTransport;
use Scott\Payment\Sdk\Http\HttpTransport;
use Scott\Payment\Sdk\Http\SdkHttpRequest;
use Scott\Payment\Sdk\Support\JsonSupport;
use Scott\Payment\Sdk\Support\OpenApiLogSanitizer;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiClient
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 商户 OpenAPI PHP SDK 客户端，负责请求签名、请求加密、响应解密、HTTP 调用和基础参数校验。支付、退款、代付和余额接口会按服务端最新协议使用 Bearer JWT 与 compact payload；本类不负责商户业务幂等落库、资金状态流转、清结算、风控或渠道回调处理。配置中包含 API 私钥、平台请求公钥和商户响应私钥，调试日志仅用于沙盒联调。
 * @status : modify
 */
final class OpenApiClient
{
    private OpenApiClientConfig $config;
    private HttpTransport $httpTransport;
    private OpenApiPayloadCrypto $payloadCrypto;
    private MerchantJwtSigner $jwtSigner;

    /**
     * 创建 OpenAPI 客户端实例。
     *
     * 构造阶段只保存配置和依赖组件，不主动访问网关、不生成 JWT、不加密报文、不修改资金或交易状态。
     * 生产环境通常不传自定义 transport，SDK 会使用 CurlHttpTransport 真实请求网关；测试场景可以注入自定义 transport。
     *
     * @param OpenApiClientConfig $config 商户配置，包含 baseUrl、商户号、livemode、API 私钥和 RSA 密钥。
     * @param HttpTransport|null $httpTransport HTTP 传输层，传 null 时使用 curl 真实请求网关。
     * @param OpenApiPayloadCrypto|null $payloadCrypto OpenAPI 请求/响应 data 加解密组件。
     * @param MerchantJwtSigner|null $jwtSigner 商户 JWT 签名组件。
     */
    public function __construct(OpenApiClientConfig $config, ?HttpTransport $httpTransport = null, ?OpenApiPayloadCrypto $payloadCrypto = null, ?MerchantJwtSigner $jwtSigner = null)
    {
        $this->config = $config;
        $this->httpTransport = $httpTransport ?: new CurlHttpTransport();
        $this->payloadCrypto = $payloadCrypto ?: new OpenApiPayloadCrypto();
        $this->jwtSigner = $jwtSigner ?: new MerchantJwtSigner();
    }

    /**
     * 从 merchant-config.php 创建默认客户端。
     *
     * 该方法读取商户配置并创建使用 curl 传输层的真实网关客户端。方法本身不发起 HTTP 请求，后续调用支付、代付、退款等方法时才会访问 baseUrl。
     *
     * @param string|null $configPath 配置文件路径，传 null 时按默认位置查找 merchant-config.php。
     * @return self SDK 客户端。
     */
    public static function create(?string $configPath = null): self
    {
        return new self(MerchantConfigLoader::load($configPath));
    }

    /**
     * 创建收银台代收交易。
     *
     * 请求会经过基础参数校验、JWT 签名、业务请求 data 加密、HTTP 调用和响应 data 解密。
     * 使用默认传输层时会真实创建网关代收交易；本方法不处理商户侧幂等落库、资金入账、订单状态流转或渠道回调。
     *
     * @param array $request 收银台代收请求，至少包含 orderNo、currency、amount，并按网关要求提供 customer 或 customerId。
     * @return OpenApiResult 网关业务响应，HTTP 成功不代表业务成功。
     */
    public function createCheckoutPayment(array $request): OpenApiResult
    {
        return $this->createPayment($request);
    }

    /**
     * 创建本地支付直连代收交易。
     *
     * 请求会复用代收创建链路完成签名、加密和响应解密。paymentMethodData 可能包含卡号、CVC 或本地支付账户等敏感资料，调试日志会做脱敏。
     * 本方法不保存支付资料、不确认支付终态、不处理回调或资金对账。
     *
     * @param array $request 本地支付请求，通常包含 payType=PaymentType::Direct、paymentMethod 和 paymentMethodData。
     * @return OpenApiResult 代收交易响应。
     */
    public function createLocalPayment(array $request): OpenApiResult
    {
        return $this->createPayment($request);
    }

    /**
     * 检索代收交易。
     *
     * GET 请求不发送请求体，但仍携带 Bearer JWT，网关响应 data 会自动解密。查询接口不修改资金或交易状态，适合网络异常后的结果确认。
     *
     * @param string $tradeNo 平台代收交易流水号。
     * @return OpenApiResult 代收交易查询响应。
     */
    public function retrievePayment(string $tradeNo): OpenApiResult
    {
        return $this->getSecured(OpenApiEndpoint::paymentRetrieve(), $this->uniqueJwtId('PAYMENT_QUERY_'), rawurlencode($this->requireText($tradeNo, 'tradeNo')));
    }

    /**
     * 创建代收退款申请。
     *
     * 请求会加密提交到退款接口，可能触发网关退款业务校验。SDK 只负责协议封装和响应解密，不负责商户侧退款幂等、余额处理、清结算或对账。
     *
     * @param array $request 退款请求，至少包含 tradeNo、currency、amount、refundAmount；orderNo 可用于商户侧退款幂等和对账。
     * @return OpenApiResult 退款申请响应。
     */
    public function createRefund(array $request): OpenApiResult
    {
        $this->validateRefundCreateRequest($request);
        return $this->postEncrypted(OpenApiEndpoint::refundCreate(), $request, $this->uniqueJwtId('REFUND_CREATE_'));
    }

    /**
     * 检索退款申请。
     *
     * GET 请求不发送请求体，不修改资金或交易状态；响应 data 由 SDK 使用商户响应私钥解密。
     *
     * @param string $refundNo 退款标识，通常为退款申请返回的 charge/refundNo。
     * @return OpenApiResult 退款查询响应。
     */
    public function retrieveRefund(string $refundNo): OpenApiResult
    {
        return $this->getSecured(OpenApiEndpoint::refundRetrieve(), $this->uniqueJwtId('REFUND_QUERY_'), rawurlencode($this->requireText($refundNo, 'refundNo')));
    }

    /**
     * 创建代付交易。
     *
     * 请求会经过 JWT 签名和 OpenAPI data 加密，使用默认传输层时会真实向网关发起代付申请。
     * 本方法可能创建测试代付交易并影响测试余额，不负责商户侧幂等、终态保护、渠道最终出款确认或资金对账。
     *
     * @param array $request 代付请求，至少包含 orderNo、currency、amount、paymentMethod 和 paymentMethodData。
     * @return OpenApiResult 代付交易响应。
     */
    public function createPayout(array $request): OpenApiResult
    {
        $this->validatePayoutCreateRequest($request);
        return $this->postEncrypted(OpenApiEndpoint::payoutCreate(), $request, $this->uniqueJwtId('PAYOUT_CREATE_'));
    }

    /**
     * 检索代付交易。
     *
     * GET 请求无请求体，响应 data 自动解密。该接口只读取网关侧代付状态，不提交资金变更、不修改商户本地订单。
     *
     * @param string $tradeNo 平台代付交易流水号。
     * @return OpenApiResult 代付交易查询响应。
     */
    public function retrievePayout(string $tradeNo): OpenApiResult
    {
        return $this->getSecured(OpenApiEndpoint::payoutRetrieve(), $this->uniqueJwtId('PAYOUT_QUERY_'), rawurlencode($this->requireText($tradeNo, 'tradeNo')));
    }

    /**
     * 取消代付交易。
     *
     * 请求会按最新协议加密提交到代付取消接口，可能改变网关侧代付状态。tradeNo 和 orderNo 只放在加密业务报文中用于定位交易，JWT jti 每次由 SDK 重新生成以避免防重放冲突。
     *
     * @param array $request 代付取消请求，通常包含 tradeNo、orderNo 和 remark。
     * @return OpenApiResult 代付取消响应；不可取消时网关可能返回业务失败。
     */
    public function cancelPayout(array $request): OpenApiResult
    {
        $this->requireArray($request, 'request');
        return $this->postEncrypted(OpenApiEndpoint::payoutCancel(), $request, $this->uniqueJwtId('PAYOUT_CANCEL_'));
    }

    /**
     * 检索商户资金账户余额。
     *
     * 查询请求不包含资金变更指令，不修改余额、冻结金额、提现金额或清结算状态。currency 为空时查询网关默认范围，非空时放入 query string。
     *
     * @param string|null $currency 查询币种，通常为 ISO 4217 三位大写代码。
     * @return OpenApiResult 余额列表响应。
     */
    public function retrieveBalances(?string $currency = null): OpenApiResult
    {
        $path = OpenApiConstants::BALANCE_RETRIEVE_PATH;
        if ($currency !== null && trim($currency) !== '') {
            $path .= '?currency=' . rawurlencode($currency);
        }
        return $this->execute(OpenApiEndpoint::balanceInquiry(), $path, null, null, $this->uniqueJwtId('BALANCE_QUERY_'));
    }

    /**
     * 拆分 OpenAPI compact payload。
     *
     * 本方法只把 data 拆分为 protectedHeader、header、encryptedAesKey、iv、cipherText、tag，便于沙盒联调或文档核验；不会解密业务明文。
     *
     * @param string $compactPayload 请求或响应中的 compact payload。
     * @return array 五段结构和 header 明文。
     */
    public function splitPayload(string $compactPayload): array
    {
        return $this->payloadCrypto->splitCompactPayload($compactPayload)->toArray();
    }

    /**
     * 创建代收交易的统一内部入口。
     *
     * 收银台和本地支付直连都会进入该方法，统一完成最小字段校验和加密 POST 调用；是否真实创建交易取决于 HTTP 传输层。
     *
     * @param array $request 代收创建请求。
     * @return OpenApiResult 代收响应。
     */
    private function createPayment(array $request): OpenApiResult
    {
        $this->validatePaymentCreateRequest($request);
        return $this->postEncrypted(OpenApiEndpoint::paymentCreate(), $request, $this->uniqueJwtId('PAYMENT_CREATE_'));
    }

    /**
     * 发送加密 POST 请求。
     *
     * 该方法把业务请求数组序列化为 JSON，使用平台请求公钥加密为 compact payload，再封装 livemode + data 发送。
     * 本方法不做商户业务幂等，资金类请求发生网络异常时商户应优先使用查询接口确认最终状态。
     *
     * @param OpenApiEndpoint $api API 元数据。
     * @param array $plainRequest 原始业务请求，可能包含金额、客户资料或卡信息。
     * @param string $jwtId JWT 防重放标识，每次请求必须唯一。
     * @return OpenApiResult 解密后的 SDK 响应。
     */
    private function postEncrypted(OpenApiEndpoint $api, array $plainRequest, string $jwtId): OpenApiResult
    {
        $requestJson = JsonSupport::encode($plainRequest);
        $parts = $this->payloadCrypto->encryptToParts($requestJson, $this->config->getPlatformRequestPublicKey());
        $encryptedRequest = [
            'livemode' => $this->config->isLivemode(),
            'data' => $parts->toCompactPayload(),
        ];
        return $this->execute($api, $api->path, $plainRequest, $encryptedRequest, $jwtId);
    }

    /**
     * 发送带 Bearer JWT 的 GET 请求。
     *
     * GET 请求没有加密请求体，但响应 data 仍会自动解密；本方法不修改资金或交易状态。
     *
     * @param OpenApiEndpoint $api API 元数据。
     * @param string $jwtId JWT 防重放标识。
     * @param string ...$pathArgs 已完成 path 编码的路径参数。
     * @return OpenApiResult 解密后的 SDK 响应。
     */
    private function getSecured(OpenApiEndpoint $api, string $jwtId, string ...$pathArgs): OpenApiResult
    {
        return $this->execute($api, $api->formatPath(...$pathArgs), null, null, $jwtId);
    }

    /**
     * 执行底层 OpenAPI 调用并转换响应。
     *
     * 该方法统一编排 requestId 生成、JWT Header、HTTP 调用、密文响应解析、livemode 校验、响应 data 解密和调试日志输出。
     * 本方法不落库、不推进商户本地订单状态；如果 HTTP 请求已经到达网关，调用方应使用查询接口确认业务最终状态。
     *
     * @param OpenApiEndpoint $api API 元数据。
     * @param string $path 接口路径，可包含 query string。
     * @param array|null $plainRequest 原始明文业务请求，GET 请求为空。
     * @param array|null $encryptedRequest 加密请求外壳，GET 请求为空。
     * @param string $jwtId JWT 防重放标识。
     * @return OpenApiResult 解密后的 SDK 响应。
     */
    private function execute(OpenApiEndpoint $api, string $path, ?array $plainRequest, ?array $encryptedRequest, string $jwtId): OpenApiResult
    {
        $requestId = $this->uuid();
        $url = $this->config->getBaseUrl() . $path;
        $body = $encryptedRequest === null ? null : JsonSupport::encode($encryptedRequest);
        $headers = $this->headers($jwtId, $requestId, $body !== null);
        $this->logJson('API调用开始', [
            'apiName' => $api->apiName,
            'method' => $api->method,
            'path' => $path,
            'merchantId' => $this->config->getMerchantNo(),
            'requestId' => $requestId,
        ]);
        if ($this->config->isDebugRawLogEnabled()) {
            $this->logJson('请求地址', ['url' => $url]);
            if ($plainRequest !== null) {
                $this->logJson('请求原始明文报文', OpenApiLogSanitizer::sanitize($plainRequest));
            }
            if ($encryptedRequest !== null) {
                $this->logJson('请求参数拆分', $this->splitPayload((string)$encryptedRequest['data']));
                $this->logJson('请求密文参数', $encryptedRequest);
            }
        }
        $response = $this->httpTransport->execute(new SdkHttpRequest($api->method, $url, $headers, $body, $this->config->getConnectTimeoutMs(), $this->config->getReadTimeoutMs()));
        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new OpenApiHttpException('OpenAPI HTTP status is not successful: ' . $response->statusCode);
        }
        $encryptedResponse = JsonSupport::decode($response->body);
        if (!is_array($encryptedResponse)) {
            throw new OpenApiResponseException('OpenAPI response body must be json object');
        }
        if ($this->config->isDebugRawLogEnabled()) {
            $this->logJson('响应原始密文参数', [
                'statusCode' => $response->statusCode,
                'headers' => OpenApiLogSanitizer::sanitizeHeaders($response->headers),
                'body' => $encryptedResponse,
            ]);
            if (!empty($encryptedResponse['data'])) {
                $this->logJson('响应参数拆分', $this->splitPayload((string)$encryptedResponse['data']));
            }
        }
        $livemode = (bool)($encryptedResponse['livemode'] ?? false);
        if ($livemode !== $this->config->isLivemode()) {
            throw new OpenApiResponseException('OpenAPI response livemode mismatch');
        }
        $data = null;
        if (!empty($encryptedResponse['data'])) {
            $plainJson = $this->payloadCrypto->decrypt((string)$encryptedResponse['data'], $this->config->getMerchantResponsePrivateKey());
            $data = JsonSupport::decode($plainJson);
            if ($this->config->isDebugRawLogEnabled()) {
                $this->logJson('响应原始明文参数', OpenApiLogSanitizer::sanitize($data));
            }
        }
        return new OpenApiResult((int)($encryptedResponse['code'] ?? -1), (string)($encryptedResponse['msg'] ?? ''), $livemode, $data);
    }

    /**
     * 构建 OpenAPI 请求头。
     *
     * 方法会签发 Bearer JWT，并按请求类型补充 Accept、User-Agent、X-Request-Id 和 Content-Type。
     * Authorization 属于敏感鉴权材料，调试日志只输出脱敏值。
     *
     * @param string $jwtId JWT jti 防重放标识。
     * @param string $requestId SDK 本地链路请求 ID。
     * @param bool $hasBody 是否存在 POST 请求体。
     * @return array HTTP Header。
     */
    private function headers(string $jwtId, string $requestId, bool $hasBody): array
    {
        $jwt = $this->jwtSigner->sign(
            $this->config->getMerchantNo(),
            $this->config->getApiPrivateKey(),
            $this->config->isLivemode(),
            $jwtId
        );
        $headers = [
            OpenApiConstants::HEADER_AUTHORIZATION => OpenApiConstants::AUTHORIZATION_PREFIX . $jwt,
            OpenApiConstants::HEADER_USER_AGENT => OpenApiConstants::USER_AGENT,
            OpenApiConstants::HEADER_ACCEPT => OpenApiConstants::ACCEPT,
            OpenApiConstants::HEADER_REQUEST_ID => $requestId,
        ];
        if ($hasBody) {
            $headers[OpenApiConstants::HEADER_CONTENT_TYPE] = OpenApiConstants::CONTENT_TYPE;
        }
        if ($this->config->isDebugRawLogEnabled()) {
            $this->logJson('请求头', OpenApiLogSanitizer::sanitizeHeaders($headers));
        }
        return $headers;
    }

    /**
     * 校验代收创建请求的最小必要字段。
     *
     * 该校验只确保 SDK 能构造加密请求，不替代网关侧支付方式参数校验、风控校验或商户侧幂等校验。
     *
     * @param array $request 代收创建请求。
     */
    private function validatePaymentCreateRequest(array $request): void
    {
        $this->requireArray($request, 'payment request');
        $this->requireText($request['orderNo'] ?? null, 'orderNo');
        $this->requireText($request['currency'] ?? null, 'currency');
        $this->requireValue($request['amount'] ?? null, 'amount');
    }

    /**
     * 校验代付创建请求的最小必要字段。
     *
     * 该校验不判断余额、风控、支付方式资料完整性或渠道限制；这些规则由网关继续校验。
     *
     * @param array $request 代付创建请求。
     */
    private function validatePayoutCreateRequest(array $request): void
    {
        $this->requireArray($request, 'payout request');
        $this->requireText($request['orderNo'] ?? null, 'orderNo');
        $this->requireText($request['currency'] ?? null, 'currency');
        $this->requireValue($request['amount'] ?? null, 'amount');
        $this->requireText($request['paymentMethod'] ?? null, 'paymentMethod');
        $this->requireArray($request['paymentMethodData'] ?? null, 'paymentMethodData');
    }

    /**
     * 校验退款创建请求的最小必要字段。
     *
     * 该校验不计算可退金额、不判断原交易状态、不处理退款幂等；这些资金和状态规则由网关及商户业务系统负责。
     *
     * @param array $request 退款创建请求。
     */
    private function validateRefundCreateRequest(array $request): void
    {
        $this->requireArray($request, 'refund request');
        $this->requireText($request['tradeNo'] ?? null, 'tradeNo');
        $this->requireText($request['currency'] ?? null, 'currency');
        $this->requireValue($request['amount'] ?? null, 'amount');
        $this->requireValue($request['refundAmount'] ?? null, 'refundAmount');
    }

    /**
     * 校验字段必须为非空数组。
     *
     * @param mixed $value 待校验字段。
     * @param string $name 字段名称。
     */
    private function requireArray($value, string $name): void
    {
        if (!is_array($value) || $value === []) {
            throw new OpenApiValidationException($name . ' can not be empty');
        }
    }

    /**
     * 校验字段必须为非空字符串。
     *
     * @param mixed $value 待校验字段。
     * @param string $name 字段名称。
     * @return string 原始字符串值。
     */
    private function requireText($value, string $name): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new OpenApiValidationException($name . ' can not be blank');
        }
        return $value;
    }

    /**
     * 校验字段必须存在且不为空。
     *
     * @param mixed $value 待校验字段。
     * @param string $name 字段名称。
     */
    private function requireValue($value, string $name): void
    {
        if ($value === null || $value === '') {
            throw new OpenApiValidationException($name . ' can not be empty');
        }
    }

    /**
     * 生成单次请求唯一 JWT jti。
     *
     * jti 只用于网关鉴权层防重放，不承担业务幂等职责；业务幂等仍应使用 orderNo、tradeNo 或 charge 等业务字段。
     *
     * @param string $prefix 场景前缀。
     * @return string 唯一 jti。
     */
    private function uniqueJwtId(string $prefix): string
    {
        return OrderNoGenerator::generate($prefix);
    }

    /**
     * 生成 SDK 本地链路 requestId。
     *
     * requestId 用于日志关联和网关排查，不参与业务幂等、签名源或资金状态判断。
     *
     * @return string UUID v4 字符串。
     */
    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * 输出商户联调 JSON 日志。
     *
     * 调用方必须在传入前完成敏感字段脱敏；生产环境建议关闭 debug_raw_log_enabled。
     *
     * @param string $name 日志名称。
     * @param mixed $value 日志字段。
     */
    private function logJson(string $name, $value): void
    {
        echo $name . ': ' . JsonSupport::encode($value) . PHP_EOL;
    }
}
