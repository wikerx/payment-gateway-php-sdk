<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : RefundTradeStatus
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : RefundTradeStatus 枚举定义，负责为商户 SDK 示例和响应解析提供固定取值或状态映射。本类只做本地常量/映射表达，不执行签名、加密、HTTP 调用、资金计算或交易状态流转。
 * @status : modify
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

    /**
     * 根据网关返回的退款 status 获取本地说明。
     *
     * 该方法只做响应展示和示例日志映射，不计算可退金额、不更新退款单、不处理清结算或对账。
     *
     * @param mixed $status 网关返回的退款状态。
     * @return array 状态名称、业务 code、说明和是否终态。
     */
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
