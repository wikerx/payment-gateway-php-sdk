<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';

$client = openapi_client();
$result = $client->retrieveBalances('USD');
log_result('检索余额真实调用-响应原始明文参数', $result->toArray());
