<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OrderNoGeneratorTest
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 订单号生成测试，负责验证单进程内连续生成不重复和前缀过滤。本测试不依赖数据库、Redis 或外部服务。
 * @status : modify
 */
final class OrderNoGeneratorTest extends TestCase
{
    /**
     * 验证同一进程内连续生成订单号不重复。
     *
     * 本 case 不依赖数据库、Redis 或分布式锁，只覆盖 SDK 示例本地快速生成 merchantOrderNo 的能力。
     */
    public function testGenerateShouldReturnUniqueOrderNoInSingleProcess(): void
    {
        $values = [];
        for ($index = 0; $index < 1000; $index++) {
            $values[] = OrderNoGenerator::generate('PAY_');
        }

        self::assertCount(1000, array_unique($values));
        self::assertMatchesRegularExpression('/^PAY_\d{20}$/', $values[0]);
    }

    /**
     * 验证订单号前缀会过滤非法字符。
     *
     * 本 case 用于保障商户传入中文、空格或特殊字符时不会影响订单号格式。
     */
    public function testGenerateShouldCleanPrefix(): void
    {
        self::assertMatchesRegularExpression('/^PAY_-01\d{20}$/', OrderNoGenerator::generate(' PA中文Y_#-01 '));
    }
}
