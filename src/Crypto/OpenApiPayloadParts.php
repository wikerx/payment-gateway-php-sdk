<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Crypto;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiPayloadParts
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : OpenAPI compact payload 五段结构，负责承载 protectedHeader、encryptedAesKey、iv、cipherText、tag 等加密字段。本类只用于请求加密、响应解密和沙盒日志排查，不做解密、不访问网关、不修改资金状态。
 * @status : modify
 */
final class OpenApiPayloadParts
{
    /**
     * Base64Url 编码后的受保护头。
     *
     * 是否敏感：否。
     * 用途：作为 AES-GCM AAD 参与认证。
     */
    private string $protectedHeader;

    /**
     * 解码后的头部 JSON。
     *
     * 是否敏感：否。
     * 用途：便于商户阅读 typ、alg、enc 等加密算法字段。
     */
    private string $header;

    /**
     * RSA-OAEP-256 加密后的 AES 会话密钥。
     *
     * 是否敏感：是。
     * 用途：平台或商户使用对应私钥解开 AES 会话密钥。
     */
    private string $encryptedAesKey;

    /**
     * AES-256-GCM 初始化向量。
     *
     * 是否敏感：否。
     * 格式：Base64Url。
     */
    private string $iv;

    /**
     * AES-256-GCM 密文。
     *
     * 是否敏感：是，承载业务请求或响应的加密内容。
     */
    private string $cipherText;

    /**
     * AES-256-GCM 认证标签。
     *
     * 是否敏感：否。
     * 用途：解密时校验密文完整性。
     */
    private string $tag;

    /**
     * 创建 compact payload 五段结构对象。
     *
     * 该对象只保存加密字段，便于商户在示例中拆分、打印和对照文档；不会自行发起请求或解密报文。
     *
     * @param string $protectedHeader Base64Url 编码后的受保护头。
     * @param string $header 解码后的头部 JSON。
     * @param string $encryptedAesKey RSA 加密后的 AES 会话密钥。
     * @param string $iv AES-GCM 初始化向量。
     * @param string $cipherText AES-GCM 密文。
     * @param string $tag AES-GCM 认证标签。
     */
    public function __construct(string $protectedHeader, string $header, string $encryptedAesKey, string $iv, string $cipherText, string $tag)
    {
        $this->protectedHeader = $protectedHeader;
        $this->header = $header;
        $this->encryptedAesKey = $encryptedAesKey;
        $this->iv = $iv;
        $this->cipherText = $cipherText;
        $this->tag = $tag;
    }

    /**
     * 拼接为网关要求的 compact payload。
     *
     * @return string protectedHeader.encryptedAesKey.iv.cipherText.tag 五段格式。
     */
    public function toCompactPayload(): string
    {
        return implode('.', [$this->protectedHeader, $this->encryptedAesKey, $this->iv, $this->cipherText, $this->tag]);
    }

    /**
     * 转换为数组，便于示例日志按字段展示加密参数。
     *
     * 数组中包含 encryptedAesKey 和 cipherText，生产环境不建议输出完整内容。
     *
     * @return array 加密五段结构数组。
     */
    public function toArray(): array
    {
        return [
            'protectedHeader' => $this->protectedHeader,
            'header' => $this->header,
            'encryptedAesKey' => $this->encryptedAesKey,
            'iv' => $this->iv,
            'cipherText' => $this->cipherText,
            'tag' => $this->tag,
        ];
    }

    /**
     * 获取 Base64Url 编码后的受保护头。
     *
     * @return string 受保护头。
     */
    public function getProtectedHeader(): string
    {
        return $this->protectedHeader;
    }

    /**
     * 获取加密后的 AES 会话密钥。
     *
     * @return string Base64Url 格式的 encryptedAesKey。
     */
    public function getEncryptedAesKey(): string
    {
        return $this->encryptedAesKey;
    }

    /**
     * 获取 AES-GCM 初始化向量。
     *
     * @return string Base64Url 格式的 iv。
     */
    public function getIv(): string
    {
        return $this->iv;
    }

    /**
     * 获取 AES-GCM 密文。
     *
     * @return string Base64Url 格式的 cipherText。
     */
    public function getCipherText(): string
    {
        return $this->cipherText;
    }

    /**
     * 获取 AES-GCM 认证标签。
     *
     * @return string Base64Url 格式的 tag。
     */
    public function getTag(): string
    {
        return $this->tag;
    }
}
