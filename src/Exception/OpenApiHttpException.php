<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * OpenAPI HTTP 调用异常。
 *
 * 用于网络失败、curl 失败或 HTTP 非 2xx 响应；资金类请求发生该异常时，商户应使用查询接口确认网关最终状态。
 */
class OpenApiHttpException extends OpenApiException
{
}
