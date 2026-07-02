<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * OpenAPI 支付方式枚举。
 *
 * 本类只为 paymentMethod 字段提供固定取值，不承载支付方式扩展数据，不执行签名、加密或资金状态流转。
 */
final class PaymentMethod
{
    public const CARD = 'CARD';
    public const PAY_PAL = 'PAY_PAL';
    public const CASHAPP = 'CASHAPP';
    public const ACH_DEBIT = 'ACH_DEBIT';
    public const UPI = 'UPI';

    private function __construct()
    {
    }
}
