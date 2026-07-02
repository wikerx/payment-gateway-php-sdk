<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Crypto;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Scott\Payment\Sdk\Exception\OpenApiCryptoException;
use Scott\Payment\Sdk\OpenApiConstants;
use Scott\Payment\Sdk\Support\JsonSupport;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiPayloadCrypto
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : OpenAPI 报文混合加解密组件，负责按 RSA-OAEP-SHA256 + AES-256-GCM compact 协议加密请求 data 和解密响应 data。本类不签发 JWT、不发起 HTTP 请求、不修改支付、退款、代付或资金状态；明文只应存在于调用链内存中，普通日志不得输出。
 * @status : modify
 */
final class OpenApiPayloadCrypto
{
    private const AES_KEY_BYTES = 32;
    private const GCM_IV_BYTES = 12;
    private const GCM_TAG_BYTES = 16;

    /**
     * 使用平台请求公钥加密商户请求明文。
     *
     * 每次调用都会生成新的 AES 会话密钥和 IV，因此同一明文多次加密得到的 data 不应相同。
     * 本方法不签发 JWT、不发起 HTTP 请求、不修改支付、退款、代付或资金状态。
     *
     * @param string $plainText 请求业务 JSON 明文。
     * @param string $recipientPublicKey 平台请求公钥，支持 PEM 或 DER Base64。
     * @return string compact payload。
     */
    public function encrypt(string $plainText, string $recipientPublicKey): string
    {
        return $this->encryptToParts($plainText, $recipientPublicKey)->toCompactPayload();
    }

    /**
     * 使用平台请求公钥加密商户请求明文，并返回五段拆分字段。
     *
     * 返回的 encryptedAesKey、iv、cipherText、tag 适合沙盒联调和文档核验；生产日志不建议长期输出完整值。
     *
     * @param string $plainText 请求业务 JSON 明文。
     * @param string $recipientPublicKey 平台请求公钥。
     * @return OpenApiPayloadParts compact payload 五段结构。
     */
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

    /**
     * 使用商户响应私钥解密网关响应 data。
     *
     * 解密成功只表示报文完整性和密钥匹配通过，不代表支付、退款、代付或资金业务一定成功。
     * 调用方仍需根据响应 code、业务 status 和查询接口确认业务结果。
     *
     * @param string $compactPayload 网关响应 data。
     * @param string $receiverPrivateKey 商户响应私钥，支持 PEM 或 DER Base64。
     * @return string 响应业务 JSON 明文。
     */
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

    /**
     * 拆分 OpenAPI compact payload。
     *
     * 该方法不解密业务明文，只按 protectedHeader.encryptedAesKey.iv.cipherText.tag 拆分，便于沙盒联调和问题排查。
     *
     * @param string $compactPayload 请求或响应中的 data 字符串。
     * @return OpenApiPayloadParts 五段结构。
     */
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

    /**
     * 生成 compact payload 的 protected header。
     *
     * header 会作为 AES-GCM AAD 参与认证，防止 typ、alg、enc 被篡改。
     *
     * @return string Base64URL 编码后的 protected header。
     */
    private function encodeProtectedHeader(): string
    {
        return $this->base64Url(JsonSupport::encode([
            'typ' => OpenApiConstants::PAYLOAD_TYPE,
            'alg' => OpenApiConstants::PAYLOAD_ALG,
            'enc' => OpenApiConstants::PAYLOAD_ENC,
        ]));
    }

    /**
     * 解码并校验 compact payload 的 protected header。
     *
     * @param string $protectedHeader Base64URL 编码后的 protected header。
     * @return string header JSON 明文。
     */
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

    /**
     * 执行无 padding 的 Base64URL 编码。
     *
     * @param string $bytes 原始字节。
     * @return string Base64URL 字符串。
     */
    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * 解码无 padding 的 Base64URL 字符串。
     *
     * @param string $value Base64URL 字符串。
     * @return string 原始字节。
     */
    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new OpenApiCryptoException('OpenAPI base64url decode failed');
        }
        return $decoded;
    }
}
