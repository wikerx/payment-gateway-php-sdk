<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Support\OpenApiLogSanitizer;

final class OpenApiLogSanitizerTest extends TestCase
{
    public function testSanitizeShouldMaskSensitiveFieldsButKeepMerchantNo(): void
    {
        $value = OpenApiLogSanitizer::sanitize([
            'merNo' => '2606177036',
            'Authorization' => 'Bearer abcdefghijklmnopqrstuvwxyz',
            'paymentMethodData' => [
                'number' => '4000056655665556',
                'cvc' => '123',
            ],
        ]);

        self::assertSame('2606177036', $value['merNo']);
        self::assertSame('Bearer abcdef******wxyz', $value['Authorization']);
        self::assertSame('400005******5556', $value['paymentMethodData']['number']);
        self::assertSame('******', $value['paymentMethodData']['cvc']);
    }
}
