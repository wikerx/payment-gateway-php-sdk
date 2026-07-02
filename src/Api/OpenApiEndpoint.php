<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Api;

use Scott\Payment\Sdk\OpenApiConstants;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiEndpoint
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 商户 OpenAPI 元数据，负责集中维护 SDK 已集成接口的 API 名称、HTTP 方法和路径模板。本类不负责请求加密、响应解密、资金状态流转或幂等控制；路径常量必须与网关 OpenAPI 文档保持一致。
 * @status : modify
 */
final class OpenApiEndpoint
{
    /**
     * API 名称，用于日志展示和排查。
     */
    public string $apiName;

    /**
     * HTTP 方法，例如 GET 或 POST。
     */
    public string $method;

    /**
     * 网关路径模板，带占位符的路径需通过 formatPath 填充。
     */
    public string $path;

    /**
     * 创建接口元数据。
     *
     * 该构造只维护路径、方法和名称，不处理 JWT、加密、HTTP 调用或资金状态。
     *
     * @param string $apiName API 名称。
     * @param string $method HTTP 方法。
     * @param string $path 网关路径模板。
     */
    private function __construct(string $apiName, string $method, string $path)
    {
        $this->apiName = $apiName;
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * 获取代收创建接口元数据。
     *
     * @return self 代收创建接口定义。
     */
    public static function paymentCreate(): self
    {
        return new self('Payment Create', 'POST', OpenApiConstants::PAYMENT_CREATE_PATH);
    }

    /**
     * 获取代收交易检索接口元数据。
     *
     * @return self 代收交易检索接口定义。
     */
    public static function paymentRetrieve(): self
    {
        return new self('Payment Retrieve', 'GET', OpenApiConstants::PAYMENT_RETRIEVE_PATH);
    }

    /**
     * 获取退款申请接口元数据。
     *
     * @return self 退款申请接口定义。
     */
    public static function refundCreate(): self
    {
        return new self('Refund Create', 'POST', OpenApiConstants::REFUND_CREATE_PATH);
    }

    /**
     * 获取退款检索接口元数据。
     *
     * @return self 退款检索接口定义。
     */
    public static function refundRetrieve(): self
    {
        return new self('Refund Retrieve', 'GET', OpenApiConstants::REFUND_RETRIEVE_PATH);
    }

    /**
     * 获取代付申请接口元数据。
     *
     * @return self 代付申请接口定义。
     */
    public static function payoutCreate(): self
    {
        return new self('Payout Transfer Create', 'POST', OpenApiConstants::PAYOUT_CREATE_PATH);
    }

    /**
     * 获取代付交易检索接口元数据。
     *
     * @return self 代付交易检索接口定义。
     */
    public static function payoutRetrieve(): self
    {
        return new self('Payout Transfer Retrieve', 'GET', OpenApiConstants::PAYOUT_RETRIEVE_PATH);
    }

    /**
     * 获取代付取消接口元数据。
     *
     * @return self 代付取消接口定义。
     */
    public static function payoutCancel(): self
    {
        return new self('Payout Transfer Cancel', 'POST', OpenApiConstants::PAYOUT_CANCEL_PATH);
    }

    /**
     * 获取资金账户余额检索接口元数据。
     *
     * @return self 余额检索接口定义。
     */
    public static function balanceInquiry(): self
    {
        return new self('Fund Accounts Balance Inquiry', 'GET', OpenApiConstants::BALANCE_RETRIEVE_PATH);
    }

    /**
     * 获取客户创建接口元数据。
     *
     * @return self 客户创建接口定义。
     */
    public static function customerCreate(): self
    {
        return new self('Customer Create', 'POST', OpenApiConstants::CUSTOMER_CREATE_PATH);
    }

    /**
     * 获取客户更新接口元数据。
     *
     * @return self 客户更新接口定义。
     */
    public static function customerUpdate(): self
    {
        return new self('Customer Update', 'PUT', OpenApiConstants::CUSTOMER_UPDATE_PATH);
    }

    /**
     * 获取客户检索接口元数据。
     *
     * @return self 客户检索接口定义。
     */
    public static function customerRetrieve(): self
    {
        return new self('Customer Retrieve', 'GET', OpenApiConstants::CUSTOMER_RETRIEVE_PATH);
    }

    /**
     * 获取客户删除接口元数据。
     *
     * @return self 客户删除接口定义。
     */
    public static function customerDelete(): self
    {
        return new self('Customer Delete', 'DELETE', OpenApiConstants::CUSTOMER_DELETE_PATH);
    }

    /**
     * 获取客户列表接口元数据。
     *
     * @return self 客户列表接口定义。
     */
    public static function customerList(): self
    {
        return new self('Customer List', 'GET', OpenApiConstants::CUSTOMER_LIST_PATH);
    }

    /**
     * 格式化带路径参数的网关地址。
     *
     * 该方法只做字符串占位符替换，不做 URL 编码、不追加 query 参数；调用方应传入已确认格式的交易号、退款号等标识。
     *
     * @param string ...$args 路径占位符参数。
     * @return string 格式化后的接口路径。
     */
    public function formatPath(string ...$args): string
    {
        if (!$args) {
            return $this->path;
        }
        return sprintf($this->path, ...$args);
    }
}
