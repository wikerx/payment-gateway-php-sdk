<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * 代付交易状态映射。
 *
 * 本类只用于商户联调日志和本地判断参考，最终出款结果应以查询接口或网关异步通知为准。
 */
final class PayoutTradeStatus
{
    private const MAP = [
        0 => ['name' => 'Reviewing', 'code' => 'requires_action', 'message' => 'Req successfully', 'description' => '审核中', 'final' => false],
        1 => ['name' => 'Processing', 'code' => 'processing', 'message' => 'Processing', 'description' => '处理中', 'final' => false],
        2 => ['name' => 'Succeeded', 'code' => 'succeeded', 'message' => 'Succeeded', 'description' => '处理成功', 'final' => true],
        3 => ['name' => 'Failed', 'code' => 'failed', 'message' => 'Failed', 'description' => '处理失败', 'final' => true],
        4 => ['name' => 'Cancelled', 'code' => 'canceled', 'message' => 'Cancelled', 'description' => '已取消', 'final' => true],
    ];

    public static function fromStatus($status): array
    {
        if ($status === null || !array_key_exists((int)$status, self::MAP)) {
            return ['name' => 'Unknown', 'code' => 'unknown', 'message' => 'Unknown status', 'description' => '未知状态', 'final' => false];
        }
        return self::MAP[(int)$status];
    }

    private function __construct()
    {
    }
}
