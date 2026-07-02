<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Api;

use Scott\Payment\Sdk\OpenApiConstants;

/**
 * 商户 OpenAPI 元数据。
 *
 * 本类集中维护 SDK 已集成接口的 API 名称、HTTP 方法和路径模板，不负责请求加密、响应解密或资金状态流转。
 */
final class OpenApiEndpoint
{
    public string $apiName;
    public string $method;
    public string $path;

    private function __construct(string $apiName, string $method, string $path)
    {
        $this->apiName = $apiName;
        $this->method = $method;
        $this->path = $path;
    }

    public static function paymentCreate(): self
    {
        return new self('Payment Create', 'POST', OpenApiConstants::PAYMENT_CREATE_PATH);
    }

    public static function paymentRetrieve(): self
    {
        return new self('Payment Retrieve', 'GET', OpenApiConstants::PAYMENT_RETRIEVE_PATH);
    }

    public static function refundCreate(): self
    {
        return new self('Refund Create', 'POST', OpenApiConstants::REFUND_CREATE_PATH);
    }

    public static function refundRetrieve(): self
    {
        return new self('Refund Retrieve', 'GET', OpenApiConstants::REFUND_RETRIEVE_PATH);
    }

    public static function payoutCreate(): self
    {
        return new self('Payout Transfer Create', 'POST', OpenApiConstants::PAYOUT_CREATE_PATH);
    }

    public static function payoutRetrieve(): self
    {
        return new self('Payout Transfer Retrieve', 'GET', OpenApiConstants::PAYOUT_RETRIEVE_PATH);
    }

    public static function payoutCancel(): self
    {
        return new self('Payout Transfer Cancel', 'POST', OpenApiConstants::PAYOUT_CANCEL_PATH);
    }

    public static function balanceInquiry(): self
    {
        return new self('Fund Accounts Balance Inquiry', 'GET', OpenApiConstants::BALANCE_RETRIEVE_PATH);
    }

    public function formatPath(string ...$args): string
    {
        if (!$args) {
            return $this->path;
        }
        return sprintf($this->path, ...$args);
    }
}
