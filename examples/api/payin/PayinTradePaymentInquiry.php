<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

$tradeNo = 'pay_202607021541448605052';
$client = openapi_client();
log_result('检索代收交易真实调用-请求参数', ['tradeNo' => $tradeNo, 'requestPath' => '/pay-api/trade/payment/' . $tradeNo]);
$result = $client->retrievePayment($tradeNo);
log_result('检索代收交易真实调用-响应原始明文参数', $result->toArray());
