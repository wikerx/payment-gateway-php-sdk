<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * SDK 请求参数校验异常。
 *
 * 本异常只表示本地请求缺少最小必要字段，不替代网关侧业务校验、风控校验或资金状态校验。
 */
class OpenApiValidationException extends OpenApiException
{
}
