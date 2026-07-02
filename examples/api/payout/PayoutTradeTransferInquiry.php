<?php

declare(strict_types=1);

/**
 * 检索代付交易真实网关调用示例，负责按 tradeNo 请求 /pay-api/payout/trade/transfer/{tradeNo}。本示例只读取代付状态，不提交资金变更、不修改交易状态。
 */

require_once __DIR__ . '/../../bootstrap.php';

$tradeNo = 'payout_202607021105485695090';
$client = openapi_client();
log_result('检索代付交易真实调用-请求参数', ['tradeNo' => $tradeNo, 'requestPath' => '/pay-api/payout/trade/transfer/' . $tradeNo]);
$result = $client->retrievePayout($tradeNo);
log_result('检索代付交易真实调用-响应原始明文参数', $result->toArray());
