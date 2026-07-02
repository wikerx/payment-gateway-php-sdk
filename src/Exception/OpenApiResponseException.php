<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * OpenAPI 响应解析异常。
 *
 * 用于响应 JSON 非法、livemode 不一致、响应 data 解密失败或响应结构不符合 SDK 预期的场景。
 */
class OpenApiResponseException extends OpenApiException
{
}
