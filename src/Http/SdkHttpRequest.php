<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : SdkHttpRequest
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : SDK HTTP 请求对象，负责承载 method、url、headers、body 和超时时间。本类只描述传输层输入，不执行网络请求、不做签名或加密；headers 可能包含 Authorization，日志输出前必须脱敏。
 * @status : modify
 */
final class SdkHttpRequest
{
    public string $method;
    public string $url;
    public array $headers;
    public ?string $body;
    public int $connectTimeoutMs;
    public int $readTimeoutMs;

    /**
     * 创建 SDK HTTP 请求对象。
     *
     * 该对象可能包含 Authorization 和加密后的请求体，日志输出前必须由调用方脱敏；构造过程不发起网络请求。
     *
     * @param string $method HTTP 方法。
     * @param string $url 完整请求地址。
     * @param array $headers 请求头。
     * @param string|null $body 请求体，GET 请求为空。
     * @param int $connectTimeoutMs 连接超时时间，单位毫秒。
     * @param int $readTimeoutMs 读取超时时间，单位毫秒。
     */
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
