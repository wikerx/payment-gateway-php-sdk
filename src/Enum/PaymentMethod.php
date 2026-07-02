<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : PaymentMethod
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : PaymentMethod 枚举定义，负责为商户 SDK 示例和响应解析提供固定取值或状态映射。本类只做本地常量/映射表达，不执行签名、加密、HTTP 调用、资金计算或交易状态流转。
 * @status : modify
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
