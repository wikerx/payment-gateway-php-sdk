<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : HttpTransport
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : HTTP 传输层接口，定义 SDK 发送网关请求的最小边界。默认实现会真实请求网关，测试或商户高级扩展可以注入自定义实现；接口本身不关心签名、加密或资金状态。
 * @status : modify
 */
interface HttpTransport
{
    /**
     * 执行 HTTP 请求并返回原始响应。
     *
     * 默认实现会真实访问网关；自定义实现可用于测试或商户侧代理。该方法不负责 JWT 签名、请求体加密、响应 data 解密或资金幂等。
     *
     * @param SdkHttpRequest $request SDK HTTP 请求对象。
     * @return SdkHttpResponse SDK HTTP 响应对象。
     */
    public function execute(SdkHttpRequest $request): SdkHttpResponse;
}
