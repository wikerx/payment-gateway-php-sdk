<?php

declare(strict_types=1);

/**
 * 检索客户真实网关调用示例，负责先创建一个沙盒客户，再请求 /pay-api/mer/customers/{customerId} 读取客户资料。
 * 本示例只读取客户资料，不修改客户、交易或资金状态；响应可能包含个人信息，商户展示和日志应继续脱敏。
 */

require_once __DIR__ . '/../../bootstrap.php';

run_example(static function (): void {
    $client = openapi_client();
    $customerId = create_customer_for_case($client);
    log_result('检索客户真实调用-请求参数', [
        'customerId' => $customerId,
        'requestPath' => '/pay-api/mer/customers/' . $customerId,
    ]);
    $result = $client->retrieveCustomer($customerId);
    log_result('检索客户真实调用-响应原始明文参数', $result->toArray());
});
