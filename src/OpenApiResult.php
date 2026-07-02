<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiResult
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : SDK 通用业务响应，负责承载网关 code、msg、livemode 和解密后的 data。HTTP 2xx 不代表业务成功，商户应继续判断 isSuccess、业务 code 和 data.status；本类不修改资金、状态或密钥配置。
 * @status : modify
 */
final class OpenApiResult
{
    /**
     * 网关业务响应码。
     *
     * 是否敏感：否。
     * 用途：商户判断本次 OpenAPI 调用是否被网关业务层受理成功。
     */
    private int $code;

    /**
     * 网关业务响应说明。
     *
     * 是否敏感：否。
     * 限制：只用于排查和展示，不应作为商户系统状态流转的唯一依据。
     */
    private string $msg;

    /**
     * 网关返回的环境标识。
     *
     * 是否敏感：否。
     * 用途：用于确认本次调用是否与商户配置中的 livemode 一致。
     */
    private bool $livemode;

    /** @var mixed */
    private $data;

    /**
     * 创建 SDK 统一响应对象。
     *
     * 该对象只保存网关返回和 SDK 解密后的结果，不发起 HTTP 请求、不修改资金、不改变交易状态。
     *
     * @param int $code 网关业务响应码。
     * @param string $msg 网关业务响应说明。
     * @param bool $livemode 网关返回的环境标识。
     * @param mixed $data SDK 解密后的业务数据，可能是数组、标量或空值。
     */
    public function __construct(int $code, string $msg, bool $livemode, $data)
    {
        $this->code = $code;
        $this->msg = $msg;
        $this->livemode = $livemode;
        $this->data = $data;
    }

    /**
     * 判断网关业务响应码是否为成功。
     *
     * 该方法不判断交易终态，支付、退款、代付等交易状态仍需读取 data.status 或对应状态枚举。
     *
     * @return bool true 表示网关业务响应码为成功。
     */
    public function isSuccess(): bool
    {
        return $this->code === OpenApiConstants::RESPONSE_CODE_SUCCESS;
    }

    /**
     * 获取网关业务响应码。
     *
     * @return int 网关业务响应码。
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 获取网关业务响应说明。
     *
     * @return string 网关业务响应说明。
     */
    public function getMsg(): string
    {
        return $this->msg;
    }

    /**
     * 获取网关返回的环境标识。
     *
     * @return bool true 表示生产环境，false 表示测试环境。
     */
    public function isLivemode(): bool
    {
        return $this->livemode;
    }

    /**
     * 获取 SDK 解密后的业务数据。
     *
     * 返回内容可能包含交易号、金额、币种、状态等业务字段；SDK 不在本方法内做资金计算或状态变更。
     *
     * @return mixed 解密后的业务数据。
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 转换为数组，便于示例日志和商户调试输出。
     *
     * 该方法不会额外脱敏，调用方如需写日志应先通过 OpenApiLogSanitizer 处理卡号等敏感字段。
     *
     * @return array SDK 响应数组。
     */
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
