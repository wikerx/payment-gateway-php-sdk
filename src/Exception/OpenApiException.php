<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * SDK 根异常。
 *
 * 本异常只作为商户捕获 SDK 错误的统一父类，不承载明文报文、私钥、完整 JWT 或完整密文。
 */
class OpenApiException extends \RuntimeException
{
}
