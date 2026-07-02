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
 * 商户 OpenAPI PHP SDK 客户端。
 *
 * 本类负责请求签名、请求加密、响应解密、HTTP 调用和基础参数校验。
 * 本类不负责商户业务幂等落库、资金状态流转或渠道回调处理；支付、退款、代付和余额接口会按服务端最新协议使用 Bearer JWT 与 JWE data。
 */
final class OpenApiClient
{
    private OpenApiClientConfig $config;
    private HttpTransport $httpTransport;
    private OpenApiPayloadCrypto $payloadCrypto;
    private MerchantJwtSigner $jwtSigner;

    public function __construct(OpenApiClientConfig $config, ?HttpTransport $httpTransport = null, ?OpenApiPayloadCrypto $payloadCrypto = null, ?MerchantJwtSigner $jwtSigner = null)
    {
        $this->config = $config;
        $this->httpTransport = $httpTransport ?: new CurlHttpTransport();
        $this->payloadCrypto = $payloadCrypto ?: new OpenApiPayloadCrypto();
        $this->jwtSigner = $jwtSigner ?: new MerchantJwtSigner();
    }

    public static function create(?string $configPath = null): self
    {
        return new self(MerchantConfigLoader::load($configPath));
    }

    public function createCheckoutPayment(array $request): OpenApiResult
    {
        return $this->createPayment($request);
    }

    public function createLocalPayment(array $request): OpenApiResult
    {
        return $this->createPayment($request);
    }

    public function retrievePayment(string $tradeNo): OpenApiResult
    {
        return $this->getSecured(OpenApiEndpoint::paymentRetrieve(), $this->uniqueJwtId('PAYMENT_QUERY_'), rawurlencode($this->requireText($tradeNo, 'tradeNo')));
    }

    public function createRefund(array $request): OpenApiResult
    {
        $this->validateRefundCreateRequest($request);
        return $this->postEncrypted(OpenApiEndpoint::refundCreate(), $request, $this->uniqueJwtId('REFUND_CREATE_'));
    }

    public function retrieveRefund(string $refundNo): OpenApiResult
    {
        return $this->getSecured(OpenApiEndpoint::refundRetrieve(), $this->uniqueJwtId('REFUND_QUERY_'), rawurlencode($this->requireText($refundNo, 'refundNo')));
    }

    public function createPayout(array $request): OpenApiResult
    {
        $this->validatePayoutCreateRequest($request);
        return $this->postEncrypted(OpenApiEndpoint::payoutCreate(), $request, $this->uniqueJwtId('PAYOUT_CREATE_'));
    }

    public function retrievePayout(string $tradeNo): OpenApiResult
    {
        return $this->getSecured(OpenApiEndpoint::payoutRetrieve(), $this->uniqueJwtId('PAYOUT_QUERY_'), rawurlencode($this->requireText($tradeNo, 'tradeNo')));
    }

    public function cancelPayout(array $request): OpenApiResult
    {
        $this->requireArray($request, 'request');
        return $this->postEncrypted(OpenApiEndpoint::payoutCancel(), $request, $this->uniqueJwtId('PAYOUT_CANCEL_'));
    }

    public function retrieveBalances(?string $currency = null): OpenApiResult
    {
        $path = OpenApiConstants::BALANCE_RETRIEVE_PATH;
        if ($currency !== null && trim($currency) !== '') {
            $path .= '?currency=' . rawurlencode($currency);
        }
        return $this->execute(OpenApiEndpoint::balanceInquiry(), $path, null, null, $this->uniqueJwtId('BALANCE_QUERY_'));
    }

    public function splitPayload(string $compactPayload): array
    {
        return $this->payloadCrypto->splitCompactPayload($compactPayload)->toArray();
    }

    private function createPayment(array $request): OpenApiResult
    {
        $this->validatePaymentCreateRequest($request);
        return $this->postEncrypted(OpenApiEndpoint::paymentCreate(), $request, $this->uniqueJwtId('PAYMENT_CREATE_'));
    }

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

    private function getSecured(OpenApiEndpoint $api, string $jwtId, string ...$pathArgs): OpenApiResult
    {
        return $this->execute($api, $api->formatPath(...$pathArgs), null, null, $jwtId);
    }

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

    private function validatePaymentCreateRequest(array $request): void
    {
        $this->requireArray($request, 'payment request');
        $this->requireText($request['orderNo'] ?? null, 'orderNo');
        $this->requireText($request['currency'] ?? null, 'currency');
        $this->requireValue($request['amount'] ?? null, 'amount');
    }

    private function validatePayoutCreateRequest(array $request): void
    {
        $this->requireArray($request, 'payout request');
        $this->requireText($request['orderNo'] ?? null, 'orderNo');
        $this->requireText($request['currency'] ?? null, 'currency');
        $this->requireValue($request['amount'] ?? null, 'amount');
        $this->requireText($request['paymentMethod'] ?? null, 'paymentMethod');
        $this->requireArray($request['paymentMethodData'] ?? null, 'paymentMethodData');
    }

    private function validateRefundCreateRequest(array $request): void
    {
        $this->requireArray($request, 'refund request');
        $this->requireText($request['tradeNo'] ?? null, 'tradeNo');
        $this->requireText($request['currency'] ?? null, 'currency');
        $this->requireValue($request['amount'] ?? null, 'amount');
        $this->requireValue($request['refundAmount'] ?? null, 'refundAmount');
    }

    private function requireArray($value, string $name): void
    {
        if (!is_array($value) || $value === []) {
            throw new OpenApiValidationException($name . ' can not be empty');
        }
    }

    private function requireText($value, string $name): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new OpenApiValidationException($name . ' can not be blank');
        }
        return $value;
    }

    private function requireValue($value, string $name): void
    {
        if ($value === null || $value === '') {
            throw new OpenApiValidationException($name . ' can not be empty');
        }
    }

    private function uniqueJwtId(string $prefix): string
    {
        return OrderNoGenerator::generate($prefix);
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function logJson(string $name, $value): void
    {
        echo $name . ': ' . JsonSupport::encode($value) . PHP_EOL;
    }
}
