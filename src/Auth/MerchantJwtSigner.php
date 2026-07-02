<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Auth;

use Scott\Payment\Sdk\Exception\OpenApiValidationException;
use Scott\Payment\Sdk\OpenApiConstants;
use Scott\Payment\Sdk\Support\JsonSupport;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : MerchantJwtSigner
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 商户 JWT HS256 签名器，负责按网关鉴权约束生成 Bearer JWT。JWT 包含 merchantId、livemode、jti、iat、exp、iss 和 aud；本类不加密请求体、不发起 HTTP 请求、不修改资金或交易状态，API 私钥和 JWT 都属于敏感鉴权材料。
 * @status : modify
 */
final class MerchantJwtSigner
{
    /**
     * 签发商户 OpenAPI JWT。
     *
     * JWT 用于接口身份认证和防重放，不承载业务幂等语义；jwtId 每次请求必须唯一。
     * 本方法不加密业务请求体、不访问网关、不修改资金或交易状态。
     *
     * @param string $merchantNo 商户号。
     * @param string $secret 商户 API 私钥，HS256 签名密钥。
     * @param bool $livemode 是否生产模式。
     * @param string $jwtId JWT jti 防重放标识。
     * @param int|null $issuedAt 签发时间戳，测试可固定。
     * @param int $ttlSeconds JWT 有效秒数，不能超过网关防重放窗口。
     * @return string JWT compact 字符串。
     */
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

    /**
     * 校验 JWT 签名参数。
     *
     * 该校验避免生成网关必然拒绝的 token，不校验商户号和密钥绑定关系。
     *
     * @param string $merchantNo 商户号。
     * @param string $secret 商户 API 私钥。
     * @param string $jwtId JWT jti。
     * @param int $ttlSeconds 有效秒数。
     */
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
}
