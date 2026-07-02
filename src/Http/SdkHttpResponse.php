<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : SdkHttpResponse
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : SDK HTTP 响应对象，负责承载状态码、响应头和响应体。本类不解析业务 code、不解密响应 data、不修改资金或交易状态；响应体可能包含密文 data。
 * @status : modify
 */
final class SdkHttpResponse
{
    public int $statusCode;
    public array $headers;
    public string $body;

    /**
     * 创建 SDK HTTP 响应对象。
     *
     * 响应体仍是网关原始返回，可能包含加密 data；业务 code 判断和响应 data 解密由 OpenApiClient 后续完成。
     *
     * @param int $statusCode HTTP 状态码。
     * @param array $headers 响应头。
     * @param string $body 响应体原文。
     */
    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }
}
