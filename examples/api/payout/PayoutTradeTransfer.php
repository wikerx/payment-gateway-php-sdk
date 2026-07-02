<?php

declare(strict_types=1);

/**
 * 代付申请真实网关调用示例，负责组装订单、金额、客户资料和收款卡资料并请求 /pay-api/payout/trade/transfer。本示例可能创建沙盒代付交易，卡号和 CVC 仅用于测试环境。
 */

require_once __DIR__ . '/../../bootstrap.php';

use Scott\Payment\Sdk\Enum\PaymentMethod;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

$client = openapi_client();
$request = [
    'orderNo' => OrderNoGenerator::generate('PAYOUT_'),
    'currency' => 'USD',
    'amount' => '3.11',
    'notifyUrl' => 'http://192.168.2.47:58080/payment-sdk/api/webhook/payout',
    'clientIp' => '47.125.221.223',
    'website' => 'https://manage.forgottenthrone.com/',
    'customer' => customer_info(),
    'metadata' => 'metadata',
    'paymentMethod' => PaymentMethod::CARD,
    'paymentMethodData' => [
        'number' => '4000056655665556',
        'expMonth' => '06',
        'expYear' => '2029',
        'cvc' => '123',
    ],
];
log_result('代付申请真实调用-请求原始明文参数', $request);
$result = $client->createPayout($request);
log_result('代付申请真实调用-响应原始明文参数', $result->toArray());
