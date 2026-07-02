<?php

declare(strict_types=1);

/**
 * 收银台代收真实网关调用示例，负责组装订单、金额、客户资料和回跳地址并请求 /pay-api/trade/payment。本示例可能创建沙盒代收交易，不负责商户本地幂等、支付完成确认或资金对账。
 */

require_once __DIR__ . '/../../bootstrap.php';

use Scott\Payment\Sdk\Support\OrderNoGenerator;

$client = openapi_client();
$request = [
    'orderNo' => OrderNoGenerator::generate('PAYIN_CHECKOUT_'),
    'currency' => 'USD',
    'amount' => '12.34',
    'returnUrl' => 'https://manage.forgottenthrone.com/',
    'notifyUrl' => 'http://192.168.2.47:58080/payment-sdk/api/webhook/payin',
    'customer' => customer_info(),
    'clientIp' => '47.125.221.223',
    'website' => 'https://manage.forgottenthrone.com/',
    'metadata' => 'metadata',
];
log_result('收银台代收创建真实调用-请求原始明文参数', $request);
$result = $client->createCheckoutPayment($request);
log_result('收银台代收创建真实调用-响应原始明文参数', $result->toArray());
