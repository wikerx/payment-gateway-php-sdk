<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

use Scott\Payment\Sdk\Exception\OpenApiHttpException;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : CurlHttpTransport
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : curl HTTP 传输层，负责把 SDK 请求真实发送到支付网关并返回 HTTP 状态码、响应头和响应体。本类不执行 JWT 签名、不加密请求体、不解密响应 data；资金类请求一旦发送可能已到达网关。
 * @status : modify
 */
final class CurlHttpTransport implements HttpTransport
{
    /**
     * 使用 curl 真实发送网关请求。
     *
     * 请求体和 Header 已由 OpenApiClient 组装完成，本方法只处理 HTTP 传输、超时和原始响应拆分；资金类 POST 请求一旦发送可能已被网关受理。
     *
     * @param SdkHttpRequest $request SDK HTTP 请求对象。
     * @return SdkHttpResponse 包含 HTTP 状态码、响应头和响应体的对象。
     */
    public function execute(SdkHttpRequest $request): SdkHttpResponse
    {
        $ch = curl_init($request->url);
        if ($ch === false) {
            throw new OpenApiHttpException('curl init failed');
        }
        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $request->connectTimeoutMs);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $request->readTimeoutMs);
        if ($request->body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new OpenApiHttpException('OpenAPI HTTP request failed: ' . $message);
        }
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        return new SdkHttpResponse($statusCode, $this->parseHeaders($headerText), $body === false ? '' : $body);
    }

    private function parseHeaders(string $headerText): array
    {
        $headers = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($headerText)) ?: [] as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[trim($name)] = trim($value);
        }
        return $headers;
    }
}
