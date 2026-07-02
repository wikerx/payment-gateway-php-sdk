<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

/**
 * SDK 通用业务响应。
 *
 * 本类承载网关 code、msg、livemode 和解密后的 data；HTTP 2xx 不代表业务成功，商户应继续判断 isSuccess()。
 */
final class OpenApiResult
{
    private int $code;
    private string $msg;
    private bool $livemode;
    /** @var mixed */
    private $data;

    public function __construct(int $code, string $msg, bool $livemode, $data)
    {
        $this->code = $code;
        $this->msg = $msg;
        $this->livemode = $livemode;
        $this->data = $data;
    }

    public function isSuccess(): bool
    {
        return $this->code === OpenApiConstants::RESPONSE_CODE_SUCCESS;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    public function isLivemode(): bool
    {
        return $this->livemode;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'msg' => $this->msg,
            'livemode' => $this->livemode,
            'data' => $this->data,
        ];
    }
}
