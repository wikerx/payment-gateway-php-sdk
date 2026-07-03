<?php

declare(strict_types=1);

/**
 * 检索退款真实网关调用示例，负责按 charge/refundNo 请求 /pay-api/trade/refund/{refundNo}。本示例只读取退款结果，不提交退款、不修改资金状态。
 */

require_once __DIR__ . '/../../../bootstrap.php';

run_example(static function (): void {
    $charge = 'charge_202607021549576341310';
    $client = openapi_client();
    log_result('检索退款真实调用-请求参数', ['charge' => $charge, 'requestPath' => '/pay-api/trade/refund/' . $charge]);
    $result = $client->retrieveRefund($charge);
    log_result('检索退款真实调用-响应原始明文参数', $result->toArray());
});
