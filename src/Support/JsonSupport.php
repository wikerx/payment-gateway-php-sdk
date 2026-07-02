<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Support;

use Scott\Payment\Sdk\Exception\OpenApiResponseException;

/**
 * JSON 编解码工具。
 *
 * 本类统一控制 SDK 请求、响应、日志 JSON 的编解码规则；日志输出前调用方仍需先做敏感字段脱敏。
 */
final class JsonSupport
{
    public static function encode($value): string
    {
        $json = json_encode(self::normalizeForJson($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if ($json === false) {
            throw new OpenApiResponseException('OpenAPI json serialization failed');
        }
        return $json;
    }

    public static function decode(string $json, bool $assoc = true)
    {
        $value = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new OpenApiResponseException('OpenAPI json parse failed: ' . json_last_error_msg());
        }
        return $value;
    }

    public static function normalizeForJson($value, ?string $fieldName = null)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if ($item === null) {
                    continue;
                }
                $result[$key] = self::normalizeForJson($item, is_string($key) ? $key : null);
            }
            return $result;
        }
        if ($fieldName !== null && self::isDecimalField($fieldName) && is_string($value) && is_numeric($value)) {
            return $value + 0;
        }
        return $value;
    }

    private static function isDecimalField(string $fieldName): bool
    {
        return in_array($fieldName, ['amount', 'refundAmount', 'balance', 'frozenAmounts', 'withdrawnAmounts'], true);
    }

    private function __construct()
    {
    }
}
