<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Auth\MerchantJwtSigner;

final class MerchantJwtSignerTest extends TestCase
{
    public function testSignShouldBuildMerchantJwtClaims(): void
    {
        $token = (new MerchantJwtSigner())->sign(
            '2606177036',
            'pi_test_IiLeEu803nK1p8nt8KY9ENPmWrnLKuwKV4MyrGoYtjr78O6317yWhl4CnELIf1tFse53fhErDCthW7ecoi5XlFOoAd0yxdf1fvo',
            false,
            'BALANCE_QUERY_20260702153025987000',
            1782813258,
            180
        );

        $parts = explode('.', $token);
        self::assertCount(3, $parts);
        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        self::assertSame('JWT', $header['typ']);
        self::assertSame('HS256', $header['alg']);
        self::assertSame(['gateway'], $payload['aud']);
        self::assertSame('merchant', $payload['iss']);
        self::assertSame('2606177036', $payload['merchantId']);
        self::assertFalse($payload['livemode']);
        self::assertSame(1782813258, $payload['iat']);
        self::assertSame(1782813438, $payload['exp']);
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
