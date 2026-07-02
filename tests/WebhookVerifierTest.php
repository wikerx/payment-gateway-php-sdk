<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Webhook\PayinWebhookVerifier;
use Scott\Payment\Sdk\Webhook\PayoutWebhookVerifier;

final class WebhookVerifierTest extends TestCase
{
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
