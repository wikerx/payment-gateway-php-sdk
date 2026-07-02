<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Support\JsonSupport;

final class JsonSupportTest extends TestCase
{
    public function testEncodeShouldKeepAmountAsJsonNumberAndCardNoAsString(): void
    {
        $json = JsonSupport::encode([
            'amount' => '12.34',
            'refundAmount' => '1.00',
            'phone' => '13628173752',
            'paymentMethodData' => [
                'number' => '4000056655665556',
                'expMonth' => '06',
            ],
        ]);

        self::assertStringContainsString('"amount":12.34', $json);
        self::assertStringContainsString('"refundAmount":1.0', $json);
        self::assertStringContainsString('"phone":"13628173752"', $json);
        self::assertStringContainsString('"number":"4000056655665556"', $json);
        self::assertStringContainsString('"expMonth":"06"', $json);
    }
}
