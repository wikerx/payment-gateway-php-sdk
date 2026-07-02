<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

/**
 * HTTP 传输层接口。
 *
 * SDK 默认实现会真实请求网关；测试或商户高级扩展可以注入自定义实现。
 */
interface HttpTransport
{
    public function execute(SdkHttpRequest $request): SdkHttpResponse;
}
