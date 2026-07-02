<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Webhook\PayinWebhookVerifier;
use Scott\Payment\Sdk\Webhook\PayoutWebhookVerifier;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : WebhookVerifierTest
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 回调验签测试，负责验证 payin 和 payout 回调签名原文拼接规则及 SHA-256 校验。本测试不启动 HTTP 服务、不落库、不修改资金状态。
 * @status : modify
 */
final class WebhookVerifierTest extends TestCase
{
    /**
     * 验证代收回调签名原文和签名结果。
     *
     * 本 case 不启动 HTTP 服务、不落库；只确认 SDK 文档中的 payin 回调验签规则可被商户直接复用。
     */
    public function testPayinWebhookSignSourceShouldMatchGatewayRule(): void
    {
        $request = [
            'tradeNo' => 'pay_123',
            'orderNo' => 'ORDER_123',
            'currency' => 'USD',
            'amount' => '12.3400',
            'status' => 2,
            'code' => 'succeeded',
            'message' => 'Succeeded',
        ];

        $verifier = new PayinWebhookVerifier();

        self::assertSame('1782901024000pay_123ORDER_123USD12.342succeededSucceeded', $verifier->buildSignSource('1782901024000', $request));
        self::assertTrue($verifier->verify('1782901024000', $verifier->sign('1782901024000', $request), $request));
    }

    /**
     * 验证代付回调签名原文和签名结果。
     *
     * 本 case 不修改代付状态、不触发资金处理；只确认 payout 回调验签字段顺序与网关规则一致。
     */
    public function testPayoutWebhookSignSourceShouldMatchGatewayRule(): void
    {
        $request = [
            'tradeNo' => 'payout_123',
            'currency' => 'USD',
            'amount' => '3.1100',
            'status' => 3,
            'code' => '1040001003',
            'message' => 'Failed',
        ];

        $verifier = new PayoutWebhookVerifier();

        self::assertSame('1782901024000payout_123USD3.1131040001003Failed', $verifier->buildSignSource('1782901024000', $request));
        self::assertTrue($verifier->verify('1782901024000', $verifier->sign('1782901024000', $request), $request));
    }
}
