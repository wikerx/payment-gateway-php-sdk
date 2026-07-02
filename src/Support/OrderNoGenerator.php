<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Support;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OrderNoGenerator
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 商户订单号生成器，负责为 SDK 示例和商户本地快速生成订单号。生成结果以时间为主并带进程内序号，只保证单 PHP 进程内尽量不重复；不依赖数据库、Redis 或外部服务，不提供分布式全局唯一能力。
 * @status : modify
 */
final class OrderNoGenerator
{
    private static int $lastMillis = -1;
    private static int $sequence = 0;

    /**
     * 生成商户订单号或 JWT jti。
     *
     * 格式为 prefix + yyyyMMddHHmmssSSS + seq，其中 seq 为同一毫秒内 000-999 的进程内递增序号。
     *
     * @param string $prefix 可选前缀，只保留字母、数字、下划线和中划线。
     * @return string 订单号或 jti。
     */
    public static function generate(string $prefix = ''): string
    {
        $current = self::currentMillis();
        if ($current === self::$lastMillis) {
            self::$sequence++;
            if (self::$sequence > 999) {
                do {
                    usleep(1000);
                    $current = self::currentMillis();
                } while ($current <= self::$lastMillis);
                self::$sequence = 0;
            }
        } else {
            self::$sequence = 0;
        }
        self::$lastMillis = $current;
        $seconds = (int)floor($current / 1000);
        $millis = $current % 1000;
        return self::cleanPrefix($prefix) . date('YmdHis', $seconds) . sprintf('%03d%03d', $millis, self::$sequence);
    }

    private static function cleanPrefix(string $prefix): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $prefix) ?? '';
    }

    private static function currentMillis(): int
    {
        return (int)floor(microtime(true) * 1000);
    }

    private function __construct()
    {
    }
}
