<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

/**
 * OpenAPI SDK 固定协议常量。
 *
 * 本类只集中维护配置文件名、HTTP Header、JWT、compact payload 和商户接口路径，不读取配置、不执行签名、
 * 不加密报文、不发起 HTTP 请求，也不修改支付、退款、代付或资金状态。
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
