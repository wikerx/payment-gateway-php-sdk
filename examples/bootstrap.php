<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Scott\Payment\Sdk\Config\MerchantConfigLoader;
use Scott\Payment\Sdk\OpenApiClient;

function openapi_client(): OpenApiClient
{
    return new OpenApiClient(MerchantConfigLoader::load(__DIR__ . '/../config/merchant-config.php'));
}

function log_result(string $title, $value): void
{
    echo $title . ': ' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
}

function customer_info(): array
{
    return [
        'firstname' => 'Lily',
        'lastname' => 'Brown',
        'email' => 'lily_brown_1782457030419@test.com',
        'phone' => '13628173752',
        'country' => 'US',
        'state' => 'CA',
        'city' => 'Los Angeles',
        'address' => '123 Main St, Apt 4B',
        'zipcode' => '90001',
    ];
}
