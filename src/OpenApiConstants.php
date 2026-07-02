<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiConstants
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : OpenAPI SDK 固定协议常量，负责集中维护配置文件名、HTTP Header、JWT、compact payload 和商户接口路径。本类不读取商户配置、不执行签名或加密、不修改资金状态；涉及路径和协议值的变更必须与网关 OpenAPI 规范同步。
 * @status : modify
 */
final class OpenApiConstants
{
    public const CONFIG_FILE_NAME = 'merchant-config.php';
    public const JWT_TTL_SECONDS = 180;
    public const HTTP_CONNECT_TIMEOUT_MS = 3000;
    public const HTTP_READ_TIMEOUT_MS = 10000;
    public const RESPONSE_CODE_SUCCESS = 0;

    public const CONTENT_TYPE = 'application/json; charset=UTF-8';
    public const ACCEPT = 'application/json';
    public const AUTHORIZATION_PREFIX = 'Bearer ';

    public const HEADER_AUTHORIZATION = 'Authorization';
    public const HEADER_CONTENT_TYPE = 'Content-Type';
    public const HEADER_ACCEPT = 'Accept';
    public const HEADER_USER_AGENT = 'User-Agent';
    public const HEADER_REQUEST_ID = 'X-Request-Id';

    public const JWT_TYPE = 'JWT';
    public const PAYLOAD_TYPE = 'PAYMENT-PAYLOAD';
    public const PAYLOAD_ALG = 'RSA-OAEP-256';
    public const PAYLOAD_ENC = 'A256GCM';

    public const SDK_NAME = 'payment-gateway-php-sdk';
    public const SDK_VERSION = '0.1.0';
    public const USER_AGENT = self::SDK_NAME . '/' . self::SDK_VERSION . ' php';

    public const PAYMENT_CREATE_PATH = '/pay-api/trade/payment';
    public const PAYMENT_RETRIEVE_PATH = '/pay-api/trade/payment/%s';
    public const REFUND_CREATE_PATH = '/pay-api/trade/refund';
    public const REFUND_RETRIEVE_PATH = '/pay-api/trade/refund/%s';
    public const PAYOUT_CREATE_PATH = '/pay-api/payout/trade/transfer';
    public const PAYOUT_RETRIEVE_PATH = '/pay-api/payout/trade/transfer/%s';
    public const PAYOUT_CANCEL_PATH = '/pay-api/payout/trade/transfer-cancel';
    public const BALANCE_RETRIEVE_PATH = '/pay-api/fund/accounts/get';
    public const CUSTOMER_CREATE_PATH = '/pay-api/mer/customers';
    public const CUSTOMER_RETRIEVE_PATH = '/pay-api/mer/customers/%s';

    private function __construct()
    {
    }
}
