<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * 退款交易状态映射。
 *
 * 本类只用于 SDK 响应解析和商户日志展示，不提交退款、不推进退款状态、不处理资金对账。
 */
final class RefundTradeStatus
{
    private const MAP = [
        -1 => ['name' => 'CreatedError', 'code' => 'error', 'message' => 'Error', 'final' => true],
        0 => ['name' => 'Created', 'code' => 'requires_action', 'message' => 'Pending', 'final' => false],
        1 => ['name' => 'Paying', 'code' => 'requires_action', 'message' => 'Processing', 'final' => false],
        2 => ['name' => 'Success', 'code' => 'succeeded', 'message' => 'Succeeded', 'final' => true],
        3 => ['name' => 'Fail', 'code' => 'failed', 'message' => 'Failed', 'final' => true],
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
