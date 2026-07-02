<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * OpenAPI 加解密异常。
 *
 * 用于 RSA-OAEP-256、AES-256-GCM、Base64URL 或 compact payload 解析失败场景，异常消息不得携带明文或密钥。
 */
class OpenApiCryptoException extends OpenApiException
{
}
