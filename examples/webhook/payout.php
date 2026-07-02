<?php

declare(strict_types=1);

/**
 * 代付回调接收示例，负责读取 GET/JSON 参数、校验 t 和 signature，并在验签通过后返回 success。生产环境必须在此处补充幂等、金额币种核对、终态保护和订单状态更新。
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Scott\Payment\Sdk\Support\JsonSupport;
use Scott\Payment\Sdk\Webhook\PayoutWebhookVerifier;

$request = $_GET + JsonSupport::decode(file_get_contents('php://input') ?: '{}');
$timestamp = $_SERVER['HTTP_T'] ?? '';
$signature = $_SERVER['HTTP_SIGNATURE'] ?? '';

$verifier = new PayoutWebhookVerifier();
if (!$verifier->verify($timestamp, $signature, $request)) {
    http_response_code(401);
    echo 'invalid signature';
    return;
}

// 生产环境请在这里基于 tradeNo/orderNo 做幂等、金额币种核对、终态保护和订单状态更新。
echo 'success';
