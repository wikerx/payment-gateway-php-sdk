<?php

declare(strict_types=1);

/**
 * 检索代收交易真实网关调用示例，负责按 tradeNo 请求 /pay-api/trade/payment/{tradeNo}。本示例只读取交易状态，不创建扣款、不退款、不修改交易状态。
 */

require_once __DIR__ . '/../../bootstrap.php';

run_example(static function (): void {
    $tradeNo = 'pay_202607021541448605052';
    $client = openapi_client();
    log_result('检索代收交易真实调用-请求参数', ['tradeNo' => $tradeNo, 'requestPath' => '/pay-api/trade/payment/' . $tradeNo]);
    $result = $client->retrievePayment($tradeNo);
    log_result('检索代收交易真实调用-响应原始明文参数', $result->toArray());
});
