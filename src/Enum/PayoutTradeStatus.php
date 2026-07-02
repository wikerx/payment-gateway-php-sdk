<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : PayoutTradeStatus
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : PayoutTradeStatus 枚举定义，负责为商户 SDK 示例和响应解析提供固定取值或状态映射。本类只做本地常量/映射表达，不执行签名、加密、HTTP 调用、资金计算或交易状态流转。
 * @status : modify
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

    /**
     * 根据网关返回的代付 status 获取本地说明。
     *
     * 该方法只用于 SDK 示例日志和商户阅读响应结果，不发起取消、不确认出款、不修改资金或交易状态。
     *
     * @param mixed $status 网关返回的代付状态。
     * @return array 状态名称、业务 code、中文说明和是否终态。
     */
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
