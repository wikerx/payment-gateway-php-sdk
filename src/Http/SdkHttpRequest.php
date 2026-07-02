<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

/**
 * SDK HTTP 请求对象。
 *
 * 本类只承载 method、url、headers、body 和超时时间，不执行网络请求、不做签名或加密。
 */
final class SdkHttpRequest
{
    public string $method;
    public string $url;
    public array $headers;
    public ?string $body;
    public int $connectTimeoutMs;
    public int $readTimeoutMs;

    public function __construct(string $method, string $url, array $headers, ?string $body, int $connectTimeoutMs, int $readTimeoutMs)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->connectTimeoutMs = $connectTimeoutMs;
        $this->readTimeoutMs = $readTimeoutMs;
    }
}
