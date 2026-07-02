<?php

declare(strict_types=1);

/**
 * 列出所有客户真实网关调用示例，负责请求 /pay-api/mer/customers 读取当前商户客户列表。
 * 本示例只读取客户列表，不创建、不更新、不删除客户资料；响应可能包含个人信息，商户日志、导出和页面展示应继续脱敏。
 */

require_once __DIR__ . '/../../bootstrap.php';

$client = openapi_client();
log_result('列出所有客户真实调用-请求参数', [
    'requestPath' => '/pay-api/mer/customers',
]);
$result = $client->listCustomers();
log_result('列出所有客户真实调用-响应原始明文参数', $result->toArray());
