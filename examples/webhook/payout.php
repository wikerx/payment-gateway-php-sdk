<?php

declare(strict_types=1);

/**
 * 代付回调接收示例，负责读取 GET/JSON 参数、校验 t 和 signature，并在验签通过后返回 success。生产环境必须在此处补充幂等、金额币种核对、终态保护和订单状态更新。
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/support.php';

use Scott\Payment\Sdk\Webhook\PayoutWebhookVerifier;

[$request, $rawBody, $bodyError] = payment_gateway_webhook_read_request();
$timestamp = $_SERVER['HTTP_T'] ?? '';
$signature = $_SERVER['HTTP_SIGNATURE'] ?? '';

if (payment_gateway_webhook_is_probe($timestamp, $signature, $request)) {
    http_response_code(200);
    echo 'payout webhook endpoint is running, waiting gateway callback';
    return;
}

if ($bodyError !== null) {
    http_response_code(400);
    echo $bodyError;
    return;
}

$verifier = new PayoutWebhookVerifier();
$verified = $verifier->verify($timestamp, $signature, $request);
payment_gateway_webhook_log('payout', [
    'headers' => payment_gateway_webhook_headers(),
    'params' => $request,
    'rawBody' => $rawBody,
    'signSource' => $verifier->buildSignSource($timestamp, $request),
    'expectedSignature' => $verifier->sign($timestamp, $request),
    'receivedSignature' => $signature,
    'verified' => $verified,
]);

if (!$verified) {
    http_response_code(401);
    echo 'invalid signature';
    return;
}

// 生产环境请在这里基于 tradeNo/orderNo 做幂等、金额币种核对、终态保护和订单状态更新。
echo 'success';
