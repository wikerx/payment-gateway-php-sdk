<?php

declare(strict_types=1);

/**
 * 检索余额真实网关调用示例，负责读取 merchant-config.php 并请求 /pay-api/fund/accounts/get?currency=USD。本示例只读余额，不修改冻结金额、提现金额、清结算或交易状态。
 */

require_once __DIR__ . '/../../../bootstrap.php';

$client = openapi_client();
$result = $client->retrieveBalances('USD');
log_result('检索余额真实调用-响应原始明文参数', $result->toArray());
