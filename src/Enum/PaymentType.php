<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * 代收支付类型枚举。
 *
 * 本类只为 payType 字段提供固定取值，不执行请求加密、资金扣款、幂等或状态流转。
 */
final class PaymentType
{
    public const Checkout = 0;
    public const Direct = 1;
    public const WebSDK = 5;
    public const Recurring = 6;

    private function __construct()
    {
    }
}
