<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Crypto;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : RsaKeyUtils
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : RSA 密钥文本规范化工具，负责兼容商户传入 PEM 文本或 DER Base64 文本。本类不执行加解密、不访问网关、不修改密钥配置；返回值可能包含完整密钥材料，调用方不得输出到普通日志。
 * @status : modify
 */
final class RsaKeyUtils
{
    /**
     * 标准化平台请求公钥。
     *
     * 支持 PEM 文本和 DER Base64 文本两种输入，返回可被 openssl 或 phpseclib 识别的 PEM 格式。
     * 该方法不校验密钥是否属于当前商户，不执行请求加密。
     *
     * @param string $key 平台请求公钥文本。
     * @return string PUBLIC KEY PEM。
     */
    public static function publicKeyPem(string $key): string
    {
        return self::normalizePem($key, 'PUBLIC KEY');
    }

    /**
     * 标准化商户响应私钥。
     *
     * 支持 PEM 文本和 DER Base64 文本两种输入，返回可用于响应解密的 PEM 格式。
     * 返回值包含完整私钥材料，不得写入普通日志或返回给前端。
     *
     * @param string $key 商户响应私钥文本。
     * @return string PRIVATE KEY PEM。
     */
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
