<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * 代收交易状态映射。
 *
 * 本类只把网关响应 status 数字映射为商户可读说明，不替代商户系统最终状态机。
 */
final class PaymentTradeStatus
{
    private const MAP = [
        0 => ['name' => 'Created', 'code' => 'requires_action', 'message' => "Please redirect to 'redirectUrl'", 'final' => false],
        1 => ['name' => 'Paying', 'code' => 'requires_action', 'message' => 'Processing', 'final' => false],
        2 => ['name' => 'Success', 'code' => 'succeeded', 'message' => 'Succeeded', 'final' => true],
        3 => ['name' => 'Fail', 'code' => 'failed', 'message' => 'Failed', 'final' => true],
        4 => ['name' => 'Cancel', 'code' => 'canceled', 'message' => 'Customer cancelled', 'final' => true],
        5 => ['name' => 'Expired', 'code' => 'expired', 'message' => 'The transaction automatic expired', 'final' => true],
    ];

    public static function fromStatus($status): array
    {
        if ($status === null || !array_key_exists((int)$status, self::MAP)) {
            return ['name' => 'Unknown', 'code' => 'unknown', 'message' => 'Unknown status', 'final' => false];
        }
        return self::MAP[(int)$status];
    }

    private function __construct()
    {
    }
}
