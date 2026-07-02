<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

final class OrderNoGeneratorTest extends TestCase
{
    public function testGenerateShouldReturnUniqueOrderNoInSingleProcess(): void
    {
        $values = [];
        for ($index = 0; $index < 1000; $index++) {
            $values[] = OrderNoGenerator::generate('PAY_');
        }

        self::assertCount(1000, array_unique($values));
        self::assertMatchesRegularExpression('/^PAY_\d{20}$/', $values[0]);
    }

    public function testGenerateShouldCleanPrefix(): void
    {
        self::assertMatchesRegularExpression('/^PAY_-01\d{20}$/', OrderNoGenerator::generate(' PA中文Y_#-01 '));
    }
}
