<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Crypto;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Scott\Payment\Sdk\Exception\OpenApiCryptoException;
use Scott\Payment\Sdk\OpenApiConstants;
use Scott\Payment\Sdk\Support\JsonSupport;

/**
 * OpenAPI 报文混合加解密组件。
 *
 * 本类负责按 RSA-OAEP-SHA256 + AES-256-GCM compact 协议加密请求 data 和解密响应 data。
 * 本类不签发 JWT、不发起 HTTP 请求、不修改支付、退款、代付或资金状态；加解密失败时不返回明文、私钥或完整密钥材料。
 */
final class OpenApiPayloadCrypto
{
    private const AES_KEY_BYTES = 32;
    private const GCM_IV_BYTES = 12;
    private const GCM_TAG_BYTES = 16;

    public function encrypt(string $plainText, string $recipientPublicKey): string
    {
        return $this->encryptToParts($plainText, $recipientPublicKey)->toCompactPayload();
    }

    public function encryptToParts(string $plainText, string $recipientPublicKey): OpenApiPayloadParts
    {
        if ($plainText === '') {
            throw new OpenApiCryptoException('OpenAPI plain text can not be blank');
        }
        $contentKey = random_bytes(self::AES_KEY_BYTES);
        $iv = random_bytes(self::GCM_IV_BYTES);
        $protectedHeader = $this->encodeProtectedHeader();
        $tag = '';
        $cipherText = openssl_encrypt($plainText, 'aes-256-gcm', $contentKey, OPENSSL_RAW_DATA, $iv, $tag, $protectedHeader, self::GCM_TAG_BYTES);
        if ($cipherText === false || $tag === '') {
            throw new OpenApiCryptoException('OpenAPI AES-GCM encrypt failed');
        }
        $publicKey = PublicKeyLoader::load(RsaKeyUtils::publicKeyPem($recipientPublicKey))
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');
        $encryptedAesKey = $publicKey->encrypt($contentKey);
        return new OpenApiPayloadParts(
            $protectedHeader,
            $this->decodeProtectedHeader($protectedHeader),
            $this->base64Url($encryptedAesKey),
            $this->base64Url($iv),
            $this->base64Url($cipherText),
            $this->base64Url($tag)
        );
    }

    public function decrypt(string $compactPayload, string $receiverPrivateKey): string
    {
        $parts = $this->splitCompactPayload($compactPayload);
        $privateKey = PublicKeyLoader::load(RsaKeyUtils::privateKeyPem($receiverPrivateKey))
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');
        $contentKey = $privateKey->decrypt($this->base64UrlDecode($parts->getEncryptedAesKey()));
        if (!is_string($contentKey) || strlen($contentKey) !== self::AES_KEY_BYTES) {
            throw new OpenApiCryptoException('OpenAPI RSA-OAEP decrypt failed');
        }
        $plainText = openssl_decrypt(
            $this->base64UrlDecode($parts->getCipherText()),
            'aes-256-gcm',
            $contentKey,
            OPENSSL_RAW_DATA,
            $this->base64UrlDecode($parts->getIv()),
            $this->base64UrlDecode($parts->getTag()),
            $parts->getProtectedHeader()
        );
        if ($plainText === false) {
            throw new OpenApiCryptoException('OpenAPI AES-GCM decrypt failed');
        }
        return $plainText;
    }

    public function splitCompactPayload(string $compactPayload): OpenApiPayloadParts
    {
        if (trim($compactPayload) === '') {
            throw new OpenApiCryptoException('OpenAPI encrypted data can not be blank');
        }
        $parts = explode('.', $compactPayload);
        if (count($parts) !== 5) {
            throw new OpenApiCryptoException('OpenAPI encrypted data format is invalid');
        }
        $header = $this->decodeProtectedHeader($parts[0]);
        return new OpenApiPayloadParts($parts[0], $header, $parts[1], $parts[2], $parts[3], $parts[4]);
    }

    private function encodeProtectedHeader(): string
    {
        return $this->base64Url(JsonSupport::encode([
            'typ' => OpenApiConstants::PAYLOAD_TYPE,
            'alg' => OpenApiConstants::PAYLOAD_ALG,
            'enc' => OpenApiConstants::PAYLOAD_ENC,
        ]));
    }

    private function decodeProtectedHeader(string $protectedHeader): string
    {
        $headerJson = $this->base64UrlDecode($protectedHeader);
        $header = JsonSupport::decode($headerJson);
        if (($header['typ'] ?? null) !== OpenApiConstants::PAYLOAD_TYPE
            || ($header['alg'] ?? null) !== OpenApiConstants::PAYLOAD_ALG
            || ($header['enc'] ?? null) !== OpenApiConstants::PAYLOAD_ENC) {
            throw new OpenApiCryptoException('OpenAPI encrypted data header is invalid');
        }
        return $headerJson;
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new OpenApiCryptoException('OpenAPI base64url decode failed');
        }
        return $decoded;
    }
}
