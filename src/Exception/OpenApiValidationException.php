<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Exception;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiValidationException
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : OpenApiValidationException 异常类型，负责表达 SDK 在配置、签名、加解密、HTTP、响应解析或参数校验阶段的错误边界。本类不承载明文报文、私钥、完整 JWT 或完整密文，不修改任何业务状态。
 * @status : modify
 */
class OpenApiValidationException extends OpenApiException
{
}
