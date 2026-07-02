<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Auth\MerchantJwtSigner;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : MerchantJwtSignerTest
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : JWT 签名测试，负责验证商户 JWT header 和 claims 与网关鉴权规则一致。本测试不请求网关、不输出完整生产密钥、不修改资金状态。
 * @status : modify
 */
final class MerchantJwtSignerTest extends TestCase
{
    /**
     * 验证商户 JWT 的 header 和 claims。
     *
     * 本 case 不访问网关、不输出完整密钥，只确认 merchantId、livemode、iat、exp 等鉴权字段符合网关规则。
     */
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
