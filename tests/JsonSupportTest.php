<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Support\JsonSupport;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : JsonSupportTest
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : JSON 工具测试，负责验证金额字段按 JSON number 输出、卡号等字符串字段保持原样。本测试不请求网关、不执行加密、不修改资金状态。
 * @status : modify
 */
final class JsonSupportTest extends TestCase
{
    /**
     * 验证金额字段和卡号字段的 JSON 输出策略。
     *
     * 金额字段按 JSON number 输出，卡号、手机号、有效期等字段保持字符串，避免支付资料被错误数值化。
     */
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
