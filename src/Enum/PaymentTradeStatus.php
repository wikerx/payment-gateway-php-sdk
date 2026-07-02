<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Enum;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : PaymentTradeStatus
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : PaymentTradeStatus 枚举定义，负责为商户 SDK 示例和响应解析提供固定取值或状态映射。本类只做本地常量/映射表达，不执行签名、加密、HTTP 调用、资金计算或交易状态流转。
 * @status : modify
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

    /**
     * 根据网关返回的代收 status 获取本地说明。
     *
     * 该方法只做响应展示和示例日志映射，不改变商户订单状态；商户落库前仍需结合回调、查询结果、幂等和终态保护判断。
     *
     * @param mixed $status 网关返回的交易状态。
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
