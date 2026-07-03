<?php

declare(strict_types=1);

/**
 * 更新客户真实网关调用示例，负责先创建一个沙盒客户，再请求 /pay-api/mer/customers/{customerId} 更新客户资料。
 * 本示例会真实修改网关侧客户资料，不负责商户本地客户资料同步、KYC、状态流转或外部渠道同步。
 */

require_once __DIR__ . '/../../bootstrap.php';

run_example(static function (): void {
    $client = openapi_client();
    $customerId = create_customer_for_case($client);
    $request = customer_update_request();
    log_result('更新客户真实调用-请求参数', [
        'customerId' => $customerId,
        'requestPath' => '/pay-api/mer/customers/' . $customerId,
    ]);
    log_result('更新客户真实调用-请求原始明文参数', $request);
    $result = $client->updateCustomer($customerId, $request);
    log_result('更新客户真实调用-响应原始明文参数', $result->toArray());
});
