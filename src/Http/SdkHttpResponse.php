<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

/**
 * SDK HTTP 响应对象。
 *
 * 本类只承载状态码、响应头和响应体，不解析业务 code，不解密响应 data。
 */
final class SdkHttpResponse
{
    public int $statusCode;
    public array $headers;
    public string $body;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }
}
