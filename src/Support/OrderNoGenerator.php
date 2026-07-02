<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Support;

/**
 * 商户订单号生成器。
 *
 * 本类用于 SDK 示例和商户本地快速生成 merchantOrderNo，只保证当前 PHP 进程内按调用顺序尽量不重复；
 * 不依赖数据库、Redis 或外部服务，不提供分布式全局唯一能力。
 */
final class OrderNoGenerator
{
    private static int $lastMillis = -1;
    private static int $sequence = 0;

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
