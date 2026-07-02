<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * SDK 配置异常。
 *
 * 用于配置文件缺失、商户号为空、密钥为空或 URL 非法等启动期错误，不涉及资金状态修改。
 */
class OpenApiConfigException extends OpenApiException
{
}
