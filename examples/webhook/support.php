<?php

declare(strict_types=1);

use Scott\Payment\Sdk\Support\JsonSupport;

/**
 * PHP webhook 示例公共方法。
 *
 * 本文件只服务 examples/webhook 下的本地联调脚本：读取 GET/POST/JSON 参数、
 * 收集请求头并打印验签诊断日志。生产环境可参考这里的处理方式，但应补充
 * 商户自己的订单幂等、金额币种核对、终态保护和状态更新。
 */

if (!function_exists('payment_gateway_webhook_read_request')) {
    /**
     * 读取回调请求参数。
     *
     * 网关回调通常使用 GET query 参数；这里同时兼容 POST form 和 JSON body，
     * 并保持 query/form 中 amount=19.00 这类金额原始字符串不被数值化。
     *
     * @return array{0: array, 1: string, 2: string|null} [参数, 原始 body, 错误信息]
     */
    function payment_gateway_webhook_read_request(): array
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $bodyParams = [];
        if (trim($rawBody) !== '') {
            $decoded = JsonSupport::decode($rawBody);
            if (!is_array($decoded)) {
                return [[], $rawBody, 'invalid json body'];
            }
            $bodyParams = $decoded;
        }

        return [$_GET + $_POST + $bodyParams, $rawBody, null];
    }
}

if (!function_exists('payment_gateway_webhook_headers')) {
    /**
     * 收集当前 HTTP 请求头。
     *
     * PHP 内置服务器和部分 FPM 环境不一定提供 getallheaders()，因此从 $_SERVER
     * 兜底提取 HTTP_*、CONTENT_TYPE、CONTENT_LENGTH。
     *
     * @return array 请求头键值。
     */
    function payment_gateway_webhook_headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        foreach (['CONTENT_TYPE' => 'Content-Type', 'CONTENT_LENGTH' => 'Content-Length'] as $serverKey => $headerName) {
            if (isset($_SERVER[$serverKey])) {
                $headers[$headerName] = $_SERVER[$serverKey];
            }
        }
        return $headers;
    }
}

if (!function_exists('payment_gateway_webhook_is_probe')) {
    /**
     * 判断是否只是浏览器或 curl 探活请求。
     *
     * 没有 t、signature 和业务参数时，不应提示 invalid signature；这只代表回调服务
     * 已经启动，但尚未收到网关真实回调。
     *
     * @param string $timestamp Header t。
     * @param string $signature Header signature。
     * @param array $request 回调业务参数。
     * @return bool true 表示空探活请求。
     */
    function payment_gateway_webhook_is_probe(string $timestamp, string $signature, array $request): bool
    {
        return trim($timestamp) === '' && trim($signature) === '' && $request === [];
    }
}

if (!function_exists('payment_gateway_webhook_log')) {
    /**
     * 打印 webhook 诊断日志。
     *
     * 日志包含请求头、请求参数、签名原文、期望签名和验签结果，便于商户排查回调验签。
     * 回调里不包含 API 私钥或 RSA 私钥，但生产环境仍应按公司日志规范保存。
     *
     * @param string $type payin 或 payout。
     * @param array $context 日志上下文。
     */
    function payment_gateway_webhook_log(string $type, array $context): void
    {
        $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        error_log('[payment-gateway-php-sdk] ' . $type . ' webhook: ' . ($json === false ? '{}' : $json));
    }
}
