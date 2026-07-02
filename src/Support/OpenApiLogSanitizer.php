<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Support;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiLogSanitizer
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : OpenAPI 日志脱敏工具，负责在商户联调日志输出前处理 Authorization、JWT、卡号、CVC、邮箱、手机号、证件号和密钥类字段。商户号不脱敏，便于网关和 SDK 日志直接核对；本类不执行加密或业务状态处理。
 * @status : modify
 */
final class OpenApiLogSanitizer
{
    private const SENSITIVE_KEYS = [
        'authorization', 'apiPrivateKey', 'api_private_key', 'merchantResponsePrivateKey',
        'merchant_response_private_key', 'platformRequestPublicKey', 'platform_request_public_key',
        'privateKey', 'publicKey', 'token', 'secret', 'cvc', 'cvv',
    ];

    /**
     * 递归脱敏日志对象。
     *
     * 本方法只用于日志输出前处理，不修改真实请求对象，不参与签名、加密或资金状态判断。
     *
     * @param mixed $value 待脱敏数据。
     * @return mixed 脱敏后的数据。
     */
    public static function sanitize($value)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::sanitizeByKey((string)$key, $item);
            }
            return $result;
        }
        return $value;
    }

    /**
     * 脱敏 HTTP Header。
     *
     * Authorization 会保留 Bearer 前缀并脱敏 token，便于商户核对 Header 结构。
     *
     * @param array $headers HTTP Header。
     * @return array 脱敏 Header。
     */
    public static function sanitizeHeaders(array $headers): array
    {
        return self::sanitize($headers);
    }

    /**
     * 根据字段名选择脱敏策略。
     *
     * @param string $key 字段名。
     * @param mixed $value 字段值。
     * @return mixed 脱敏结果。
     */
    private static function sanitizeByKey(string $key, $value)
    {
        $normalized = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($normalized === strtolower($sensitiveKey) || strpos($normalized, strtolower($sensitiveKey)) !== false) {
                return self::maskString((string)$value);
            }
        }
        if ($normalized === 'number' || strpos($normalized, 'cardno') !== false || strpos($normalized, 'card_no') !== false) {
            return self::maskCard((string)$value);
        }
        if ($normalized === 'email') {
            return self::maskEmail((string)$value);
        }
        if ($normalized === 'phone') {
            return self::maskPhone((string)$value);
        }
        if (is_array($value)) {
            return self::sanitize($value);
        }
        return $value;
    }

    private static function maskString(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (stripos($value, 'Bearer ') === 0) {
            return 'Bearer ' . self::maskString(substr($value, 7));
        }
        if (strlen($value) <= 10) {
            return '******';
        }
        return substr($value, 0, 6) . '******' . substr($value, -4);
    }

    private static function maskCard(string $value): string
    {
        if (strlen($value) < 10) {
            return '******';
        }
        return substr($value, 0, 6) . '******' . substr($value, -4);
    }

    private static function maskEmail(string $value): string
    {
        $pos = strpos($value, '@');
        if ($pos === false || $pos === 0) {
            return self::maskString($value);
        }
        return substr($value, 0, 1) . '******' . substr($value, $pos);
    }

    private static function maskPhone(string $value): string
    {
        if (strlen($value) < 7) {
            return '******';
        }
        return substr($value, 0, 3) . '****' . substr($value, -4);
    }

    private function __construct()
    {
    }
}
