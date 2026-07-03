<?php

declare(strict_types=1);

/**
 * 创建客户真实网关调用示例，负责组装唯一测试客户资料并请求 /pay-api/mer/customers。
 * 本示例会真实创建沙盒客户，涉及姓名、邮箱、电话、地址和证件号等敏感个人信息；SDK 会加密请求 data 并脱敏调试日志。
 */

require_once __DIR__ . '/../../bootstrap.php';

run_example(static function (): void {
    $client = openapi_client();
    $request = customer_create_request();
    log_result('创建客户真实调用-请求原始明文参数', $request);
    $result = $client->createCustomer($request);
    log_result('创建客户真实调用-响应原始明文参数', $result->toArray());
});
