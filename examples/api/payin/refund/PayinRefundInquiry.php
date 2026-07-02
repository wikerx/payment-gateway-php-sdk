<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

$charge = 'charge_202607021549576341310';
$client = openapi_client();
log_result('检索退款真实调用-请求参数', ['charge' => $charge, 'requestPath' => '/pay-api/trade/refund/' . $charge]);
$result = $client->retrieveRefund($charge);
log_result('检索退款真实调用-响应原始明文参数', $result->toArray());
