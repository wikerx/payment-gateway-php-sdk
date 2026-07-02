<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use Scott\Payment\Sdk\Enum\PaymentMethod;
use Scott\Payment\Sdk\Enum\PaymentType;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

$client = openapi_client();
$request = [
    'orderNo' => OrderNoGenerator::generate('PAYIN_CASHAPP_'),
    'payType' => PaymentType::Direct,
    'currency' => 'USD',
    'amount' => '12.34',
    'notifyUrl' => 'http://192.168.2.47:58080/payment-sdk/api/webhook/payin',
    'clientIp' => '47.125.221.223',
    'website' => 'http://192.168.2.47:5173',
    'customer' => customer_info(),
    'metadata' => 'metadata',
    'paymentMethod' => PaymentMethod::CASHAPP,
    'paymentMethodData' => [
        'cashappAccount' => '$123',
        'email' => 'lily_brown_1782457030419@test.com',
    ],
];
log_result('本地支付直连代收创建真实调用-请求原始明文参数', $request);
$result = $client->createLocalPayment($request);
log_result('本地支付直连代收创建真实调用-响应原始明文参数', $result->toArray());
