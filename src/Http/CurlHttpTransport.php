<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Http;

use Scott\Payment\Sdk\Exception\OpenApiHttpException;

/**
 * curl HTTP 传输层。
 *
 * 本类负责把 SDK 请求真实发送到支付网关，不执行 JWT 签名、不加密请求体、不解密响应 data。
 */
final class CurlHttpTransport implements HttpTransport
{
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
