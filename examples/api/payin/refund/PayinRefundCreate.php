<?php

declare(strict_types=1);

/**
 * 代收退款申请真实网关调用示例，负责按原代收 tradeNo、金额和退款金额请求 /pay-api/trade/refund。本示例可能触发退款业务校验，不负责商户本地退款幂等、余额处理或对账。
 */

require_once __DIR__ . '/../../../bootstrap.php';

use Scott\Payment\Sdk\Support\OrderNoGenerator;

$client = openapi_client();
$request = [
    // 商户联调时替换为已支付成功且允许退款的代收 tradeNo。
    'tradeNo' => 'pay_202607021541448605052',
    'orderNo' => OrderNoGenerator::generate('REFUND_'),
    'currency' => 'USD',
    'amount' => '12.34',
    'refundAmount' => '1.00',
    'refundReason' => 'SDK真实调用代收退款申请',
    'metadata' => 'metadata',
];
log_result('代收退款申请真实调用-请求原始明文参数', $request);
$result = $client->createRefund($request);
log_result('代收退款申请真实调用-响应原始明文参数', $result->toArray());
