<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Support;

/**
 * OpenAPI 日志脱敏工具。
 *
 * 本类负责在商户联调日志输出前处理 Authorization、JWT、卡号、CVC、邮箱、手机号、证件号和密钥类字段。
 * 商户号不脱敏，便于网关和 SDK 日志直接核对。
 */
final class OpenApiLogSanitizer
{
    private const SENSITIVE_KEYS = [
        'authorization', 'apiPrivateKey', 'api_private_key', 'merchantResponsePrivateKey',
        'merchant_response_private_key', 'platformRequestPublicKey', 'platform_request_public_key',
        'privateKey', 'publicKey', 'token', 'secret', 'cvc', 'cvv',
    ];

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

    public static function sanitizeHeaders(array $headers): array
    {
        return self::sanitize($headers);
    }

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
