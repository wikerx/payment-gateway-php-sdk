<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Crypto;

/**
 * RSA 密钥文本规范化工具。
 *
 * 本类支持商户传入 PEM 文本或 DER Base64 文本，不执行加解密、不访问网关、不修改密钥配置。
 */
final class RsaKeyUtils
{
    public static function publicKeyPem(string $key): string
    {
        return self::normalizePem($key, 'PUBLIC KEY');
    }

    public static function privateKeyPem(string $key): string
    {
        return self::normalizePem($key, 'PRIVATE KEY');
    }

    private static function normalizePem(string $key, string $type): string
    {
        $trimmed = trim($key);
        if (strpos($trimmed, '-----BEGIN') !== false) {
            return $trimmed;
        }
        $body = chunk_split(preg_replace('/\s+/', '', $trimmed) ?? '', 64, "\n");
        return "-----BEGIN {$type}-----\n" . $body . "-----END {$type}-----";
    }

    private function __construct()
    {
    }
}
