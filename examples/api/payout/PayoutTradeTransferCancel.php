<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$client = openapi_client();
$request = [
    'tradeNo' => 'payout_202607021532396969266',
    'orderNo' => 'PAYOUT_20260702153239394000',
    'remark' => 'SDK真实调用代付取消申请',
];
log_result('代付取消申请真实调用-请求原始明文参数', $request);
$result = $client->cancelPayout($request);
log_result('代付取消申请真实调用-响应原始明文参数', $result->toArray());
