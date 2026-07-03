<?php

declare(strict_types=1);

/**
 * 删除客户真实网关调用示例，负责先创建一个沙盒客户，再请求 /pay-api/mer/customers/{customerId} 删除客户资料。
 * 本示例会真实删除网关侧客户资料，网关当前响应 data 为 true；不会删除商户本地订单、交易、退款、代付或对账记录。
 */

require_once __DIR__ . '/../../bootstrap.php';

run_example(static function (): void {
    $client = openapi_client();
    $customerId = create_customer_for_case($client);
    log_result('删除客户真实调用-请求参数', [
        'customerId' => $customerId,
        'requestPath' => '/pay-api/mer/customers/' . $customerId,
    ]);
    $result = $client->deleteCustomer($customerId);
    log_result('删除客户真实调用-响应原始明文参数', $result->toArray());
});
