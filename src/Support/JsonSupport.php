<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Support;

use Scott\Payment\Sdk\Exception\OpenApiResponseException;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : JsonSupport
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : SDK JSON 编解码工具，负责统一控制请求序列化、响应反序列化、null 字段移除和金额字段 JSON 数字化。本类不执行签名、加密、HTTP 调用或资金状态处理；序列化对象可能包含敏感业务字段，日志输出前必须脱敏。
 * @status : modify
 */
final class JsonSupport
{
    /**
     * 将对象或数组序列化为 JSON 字符串。
     *
     * 序列化时会移除 null 字段，并把 amount、refundAmount、balance 等金额字段的数字字符串转为 JSON number，避免网关收到字符串金额。
     *
     * @param mixed $value 待序列化数据。
     * @return string JSON 字符串。
     */
    public static function encode($value): string
    {
        $json = json_encode(self::normalizeForJson($value), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if ($json === false) {
            throw new OpenApiResponseException('OpenAPI json serialization failed');
        }
        return $json;
    }

    /**
     * 将 JSON 字符串反序列化。
     *
     * @param string $json JSON 字符串。
     * @param bool $assoc 是否返回关联数组。
     * @return mixed 反序列化结果。
     */
    public static function decode(string $json, bool $assoc = true)
    {
        $value = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new OpenApiResponseException('OpenAPI json parse failed: ' . json_last_error_msg());
        }
        return $value;
    }

    /**
     * 规范化 JSON 序列化输入。
     *
     * 本方法只处理 null 字段和金额字段类型，不处理卡号、手机号、邮编等需要保留前导零或完整字符串语义的字段。
     *
     * @param mixed $value 输入值。
     * @param string|null $fieldName 当前字段名。
     * @return mixed 规范化后的值。
     */
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

    /**
     * 判断字段是否应按 JSON number 输出。
     *
     * @param string $fieldName 字段名。
     * @return bool true 表示金额/余额类字段。
     */
    private static function isDecimalField(string $fieldName): bool
    {
        return in_array($fieldName, ['amount', 'refundAmount', 'balance', 'frozenAmounts', 'withdrawnAmounts'], true);
    }

    private function __construct()
    {
    }
}
