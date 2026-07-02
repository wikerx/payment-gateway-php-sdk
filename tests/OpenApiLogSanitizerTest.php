<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Support\OpenApiLogSanitizer;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiLogSanitizerTest
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 日志脱敏测试，负责验证 Authorization、卡号和 CVC 脱敏，同时保持商户号明文便于联调核对。本测试不请求网关、不执行加密。
 * @status : modify
 */
final class OpenApiLogSanitizerTest extends TestCase
{
    /**
     * 验证日志脱敏规则。
     *
     * 本 case 确认 Authorization、卡号和 CVC 会脱敏，同时商户号保持明文，便于沙盒联调核对。
     */
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
