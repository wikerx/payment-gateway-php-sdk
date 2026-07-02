<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Crypto;

/**
 * OpenAPI compact payload 五段结构。
 *
 * 本类只承载 protectedHeader、encryptedAesKey、iv、cipherText、tag 等加密字段，用于请求加密、响应解密和沙盒日志排查。
 */
final class OpenApiPayloadParts
{
    private string $protectedHeader;
    private string $header;
    private string $encryptedAesKey;
    private string $iv;
    private string $cipherText;
    private string $tag;

    public function __construct(string $protectedHeader, string $header, string $encryptedAesKey, string $iv, string $cipherText, string $tag)
    {
        $this->protectedHeader = $protectedHeader;
        $this->header = $header;
        $this->encryptedAesKey = $encryptedAesKey;
        $this->iv = $iv;
        $this->cipherText = $cipherText;
        $this->tag = $tag;
    }

    public function toCompactPayload(): string
    {
        return implode('.', [$this->protectedHeader, $this->encryptedAesKey, $this->iv, $this->cipherText, $this->tag]);
    }

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

    public function getProtectedHeader(): string
    {
        return $this->protectedHeader;
    }

    public function getEncryptedAesKey(): string
    {
        return $this->encryptedAesKey;
    }

    public function getIv(): string
    {
        return $this->iv;
    }

    public function getCipherText(): string
    {
        return $this->cipherText;
    }

    public function getTag(): string
    {
        return $this->tag;
    }
}
