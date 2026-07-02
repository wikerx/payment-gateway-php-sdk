<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Auth;

use Scott\Payment\Sdk\Exception\OpenApiValidationException;
use Scott\Payment\Sdk\OpenApiConstants;
use Scott\Payment\Sdk\Support\JsonSupport;

/**
 * 商户 JWT HS256 签名器。
 *
 * 本类负责按网关 MerchantJwtVerifier 约束生成 Bearer JWT，不加密请求体、不发起 HTTP 请求、不修改资金状态。
 * API 私钥和生成后的 JWT 都属于敏感鉴权材料，普通日志不得输出完整值。
 */
final class MerchantJwtSigner
{
    public function sign(string $merchantNo, string $secret, bool $livemode, string $jwtId, ?int $issuedAt = null, int $ttlSeconds = OpenApiConstants::JWT_TTL_SECONDS): string
    {
        $this->validate($merchantNo, $secret, $jwtId, $ttlSeconds);
        $iat = $issuedAt ?? time();
        $header = [
            'typ' => OpenApiConstants::JWT_TYPE,
            'alg' => 'HS256',
        ];
        $payload = [
            'aud' => ['gateway'],
            'iss' => 'merchant',
            'jti' => $jwtId,
            'iat' => $iat,
            'exp' => $iat + $ttlSeconds,
            'merchantId' => $merchantNo,
            'livemode' => $livemode,
        ];
        $segments = [
            $this->base64Url(JsonSupport::encode($header)),
            $this->base64Url(JsonSupport::encode($payload)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $this->base64Url($signature);
        return implode('.', $segments);
    }

    private function validate(string $merchantNo, string $secret, string $jwtId, int $ttlSeconds): void
    {
        if (trim($merchantNo) === '') {
            throw new OpenApiValidationException('merchantNo can not be blank');
        }
        if (trim($secret) === '' || strlen($secret) < 32) {
            throw new OpenApiValidationException('merchant jwt secret must be at least 256 bits for HS256');
        }
        if (trim($jwtId) === '') {
            throw new OpenApiValidationException('jwt jti can not be blank');
        }
        if ($ttlSeconds <= 0 || $ttlSeconds > OpenApiConstants::JWT_TTL_SECONDS) {
            throw new OpenApiValidationException('jwt ttlSeconds must be between 1 and ' . OpenApiConstants::JWT_TTL_SECONDS);
        }
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
