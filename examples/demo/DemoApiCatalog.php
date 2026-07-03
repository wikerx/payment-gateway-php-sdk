<?php

declare(strict_types=1);

use Scott\Payment\Sdk\OpenApiConstants;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

final class DemoApiCatalog
{
    private const CUSTOMER_JSON = "{\n  \"firstname\": \"Lily\",\n  \"lastname\": \"Brown\",\n  \"email\": \"lily_brown_1782457030419@test.com\",\n  \"phone\": \"13628173752\",\n  \"country\": \"US\",\n  \"state\": \"CA\",\n  \"city\": \"Los Angeles\",\n  \"address\": \"123 Main St, Apt 4B\",\n  \"zipcode\": \"90001\"\n}";

    private function __construct()
    {
    }

    public static function groups(): array
    {
        return [
            [
                'code' => 'customer',
                'name' => '客户',
                'description' => '创建、查询、更新、删除和列出网关客户资料。',
                'apis' => [
                    self::customerCreate(),
                    self::customerRetrieve(),
                    self::customerUpdate(),
                    self::customerDelete(),
                    self::customerList(),
                ],
            ],
            [
                'code' => 'payin',
                'name' => '代收',
                'description' => '创建收银台或直连代收交易，并查询代收结果。',
                'apis' => [
                    self::payinCheckout(),
                    self::payinDirect(),
                    self::payinRetrieve(),
                ],
            ],
            [
                'code' => 'refund',
                'name' => '退款申请',
                'description' => '基于代收交易发起退款申请，并查询退款处理结果。',
                'apis' => [
                    self::refundCreate(),
                    self::refundRetrieve(),
                ],
            ],
            [
                'code' => 'payout',
                'name' => '代付',
                'description' => '发起代付、查询代付和提交取消申请。',
                'apis' => [
                    self::payoutCreate(),
                    self::payoutRetrieve(),
                    self::payoutCancel(),
                ],
            ],
            [
                'code' => 'balance',
                'name' => '余额查询',
                'description' => '查询商户资金账户余额，可按币种过滤。',
                'apis' => [
                    self::balanceRetrieve(),
                ],
            ],
        ];
    }

    public static function get(string $code): array
    {
        foreach (self::groups() as $group) {
            foreach ($group['apis'] as $api) {
                if ($api['code'] === $code) {
                    return $api;
                }
            }
        }
        throw new InvalidArgumentException('Unsupported demo api: ' . $code);
    }

    public static function defaults(array $definition, string $merchantNo): array
    {
        $params = [];
        foreach ($definition['requestFields'] as $field) {
            $params[$field['name']] = self::resolveDefault($field['default'] ?? '', $merchantNo, $field['name']);
        }
        return $params;
    }

    public static function paymentMethodDataExamples(): array
    {
        return [
            'CASHAPP' => [
                'cashappAccount' => '$123',
                'email' => 'lily_brown_1782457030419@test.com',
            ],
            'CARD' => [
                'number' => '4000056655665556',
                'expMonth' => '06',
                'expYear' => '2029',
                'cvc' => '123',
                'email' => 'lily_brown_1782457030419@test.com',
                'holderName' => 'Lily Brown',
            ],
            'PAY_PAL' => [
                'paypalEmail' => 'lily_brown_1782457030419@test.com',
            ],
            'ACH_DEBIT' => [
                'accountNumber' => '6205500000000000004',
                'routingNumber' => '641110',
            ],
            'UPI' => [
                'bankName' => "scott's bank",
                'bankCode' => '641110',
                'cardNo' => '6200000000000005',
            ],
        ];
    }

    private static function payinCheckout(): array
    {
        return self::api('payin-checkout', 'payin', '代收', '创建收银台代收',
            '创建一笔跳转式代收交易，响应通常包含 tradeNo、redirectUrl 或 clientSecret。',
            '创建支付', 'POST', OpenApiConstants::PAYMENT_CREATE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('orderNo', '商户订单号', '商户侧唯一订单号，Demo 会自动生成。', true, 'AUTO:PAYIN_CHECKOUT_'),
                self::currencySelect(),
                self::field('amount', '金额', '主币种金额，避免使用浮点数。', true, '12.34', '12.34'),
                self::field('returnUrl', '返回地址', '支付完成后的前端跳转地址。', false, 'https://manage.forgottenthrone.com/'),
                self::field('notifyUrl', '异步通知地址', '网关支付结果通知地址。', false, 'http://192.168.2.47:58080/payment-sdk/api/webhook/payin'),
                self::field('clientIp', '客户端 IP', '付款人客户端 IP。', false, '47.125.221.223'),
                self::field('website', '商户网站', '商户站点或业务来源。', false, 'https://manage.forgottenthrone.com/'),
                self::field('metadata', '透传字段', '商户自定义透传数据。', false, 'metadata'),
                self::select('paymentMethodTypes', '可用支付方式', '收银台代收选择一个支付方式，Demo 会提交为 paymentMethodTypes 数组。', false, 'CARD', self::paymentMethodOptions()),
                self::customerMode(),
                self::customerId(),
                self::customerJson()
            ),
            self::paymentResponseFields());
    }

    private static function payinDirect(): array
    {
        return self::api('payin-direct', 'payin', '代收', '创建直连代收',
            '创建一笔直连代收交易，切换支付方式时 Demo 会自动替换 paymentMethodData。',
            '发起支付', 'POST', OpenApiConstants::PAYMENT_CREATE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('orderNo', '商户订单号', '商户侧唯一订单号，Demo 会自动生成。', true, 'AUTO:PAYIN_CASHAPP_'),
                self::select('payType', '支付类型', '直连代收固定为 1。', true, '1', [['value' => '1', 'label' => '1 - 直连']]),
                self::currencySelect(),
                self::field('amount', '金额', '主币种金额，避免使用浮点数。', true, '12.34', '12.34'),
                self::select('paymentMethod', '支付方式', '支持 CARD、CASHAPP 等网关支付方式。', true, 'CASHAPP', self::paymentMethodOptions()),
                self::json('paymentMethodData', '支付方式参数', '不同支付方式需要不同扩展参数，包含敏感信息时不要输出到生产日志。', true, self::pretty(self::paymentMethodDataExamples()['CASHAPP'])),
                self::field('notifyUrl', '异步通知地址', '网关支付结果通知地址。', false, 'http://192.168.2.47:58080/payment-sdk/api/webhook/payin'),
                self::field('clientIp', '客户端 IP', '付款人客户端 IP。', false, '47.125.221.223'),
                self::field('website', '商户网站', '商户站点或业务来源。', false, 'http://192.168.2.47:5173'),
                self::field('metadata', '透传字段', '商户自定义透传数据。', false, 'metadata'),
                self::customerMode(),
                self::customerId(),
                self::customerJson()
            ),
            self::paymentResponseFields());
    }

    private static function payinRetrieve(): array
    {
        return self::api('payin-retrieve', 'payin', '代收', '查询代收交易',
            '通过平台代收交易号查询支付结果，不会创建新交易。',
            '查询支付', 'GET', OpenApiConstants::PAYMENT_RETRIEVE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('tradeNo', '平台交易号', '创建代收返回的 tradeNo。', true, 'pay_202607021541448605052', 'pay_xxx')
            ),
            self::paymentResponseFields());
    }

    private static function refundCreate(): array
    {
        return self::api('refund-create', 'refund', '退款申请', '创建退款申请',
            '基于代收交易号提交退款申请，原交易不可退时网关会返回业务失败。',
            '申请退款', 'POST', OpenApiConstants::REFUND_CREATE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('tradeNo', '原代收交易号', '需要退款的原代收 tradeNo。', true, 'pay_202607021541448605052', 'pay_xxx'),
                self::currencySelect('必须与原交易币种一致。'),
                self::field('amount', '原交易金额', '原代收交易金额。', true, '12.34', '12.34'),
                self::field('refundAmount', '退款金额', '本次申请退款金额。', true, '1.00', '1.00'),
                self::field('refundReason', '退款原因', '提交给网关的退款原因。', true, 'SDK Demo refund request'),
                self::field('metadata', '透传字段', '商户自定义透传数据。', false, 'metadata')
            ),
            self::refundResponseFields());
    }

    private static function refundRetrieve(): array
    {
        return self::api('refund-retrieve', 'refund', '退款申请', '查询退款',
            '通过退款标识查询退款处理结果。',
            '查询退款', 'GET', OpenApiConstants::REFUND_RETRIEVE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('refundNo', '退款标识', '退款申请返回的 charge/refundNo。', true, 'charge_202607021549576341310', 'charge_xxx')
            ),
            self::refundResponseFields());
    }

    private static function payoutCreate(): array
    {
        return self::api('payout-create', 'payout', '代付', '发起代付',
            '创建一笔代付申请，币种和支付方式使用下拉选择。',
            '发起代付', 'POST', OpenApiConstants::PAYOUT_CREATE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('orderNo', '商户订单号', '商户侧唯一代付订单号，Demo 会自动生成。', true, 'AUTO:PAYOUT_'),
                self::currencySelect(),
                self::field('amount', '金额', '代付出款金额。', true, '3.11', '3.11'),
                self::select('paymentMethod', '支付方式', '收款支付方式。', true, 'CARD', self::paymentMethodOptions()),
                self::json('paymentMethodData', '支付方式参数', '收款支付方式扩展数据，可能包含卡号或银行账号。', true, self::pretty(self::paymentMethodDataExamples()['CARD'])),
                self::field('notifyUrl', '异步通知地址', '网关代付结果通知地址。', false, 'http://192.168.2.47:58080/payment-sdk/api/webhook/payout'),
                self::field('clientIp', '客户端 IP', '操作或客户 IP。', false, '47.125.221.223'),
                self::field('website', '商户网站', '商户站点或业务来源。', false, 'https://manage.forgottenthrone.com/'),
                self::field('metadata', '透传字段', '商户自定义透传数据。', false, 'metadata'),
                self::customerJson()
            ),
            self::payoutResponseFields());
    }

    private static function payoutRetrieve(): array
    {
        return self::api('payout-retrieve', 'payout', '代付', '查询代付',
            '通过平台代付交易号查询代付处理结果。',
            '查询代付', 'GET', OpenApiConstants::PAYOUT_RETRIEVE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('tradeNo', '平台代付交易号', '代付申请返回的 tradeNo。', true, 'payout_202607021105485695090', 'payout_xxx')
            ),
            self::payoutResponseFields());
    }

    private static function payoutCancel(): array
    {
        return self::api('payout-cancel', 'payout', '代付', '取消代付',
            '提交代付取消申请，已成功或不可取消的交易可能返回业务失败。',
            '取消代付', 'POST', OpenApiConstants::PAYOUT_CANCEL_PATH,
            self::fields(
                self::merchantNo(),
                self::field('tradeNo', '平台代付交易号', '代付申请返回的 tradeNo。', true, 'payout_202607021532396969266', 'payout_xxx'),
                self::field('orderNo', '商户订单号', '代付申请时的商户订单号。', true, 'PAYOUT_20260702153239394000'),
                self::field('remark', '备注', '取消原因或联调备注。', false, 'SDK Demo payout cancel')
            ),
            self::payoutResponseFields());
    }

    private static function balanceRetrieve(): array
    {
        return self::api('balance-retrieve', 'balance', '余额查询', '查询资金账户余额',
            '查询商户余额，可填写 currency 只查询某个币种。',
            '查询余额', 'GET', OpenApiConstants::BALANCE_RETRIEVE_PATH,
            self::fields(
                self::merchantNo(),
                self::currencySelect('为空则查询全部币种。', false)
            ),
            self::balanceResponseFields());
    }

    private static function customerCreate(): array
    {
        return self::api('customer-create', 'customer', '客户', '创建客户',
            '创建一条网关客户资料，邮箱和证件号 Demo 会生成唯一值。',
            '创建客户', 'POST', OpenApiConstants::CUSTOMER_CREATE_PATH,
            self::customerRequestFields(false),
            self::customerResponseFields());
    }

    private static function customerRetrieve(): array
    {
        return self::api('customer-retrieve', 'customer', '客户', '查询客户',
            '通过 customerId 查询客户资料。',
            '查询客户', 'GET', OpenApiConstants::CUSTOMER_RETRIEVE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('customerId', '客户 ID', '创建客户返回的 customerId。', true, 'cus_ysNthtSsNGrwje3', 'cus_xxx')
            ),
            self::customerResponseFields());
    }

    private static function customerUpdate(): array
    {
        return self::api('customer-update', 'customer', '客户', '更新客户',
            '更新网关客户资料，customerId 使用路径参数，请求体只提交可更新字段。',
            '更新客户', 'PUT', OpenApiConstants::CUSTOMER_UPDATE_PATH,
            array_merge(self::fields(
                self::merchantNo(),
                self::field('customerId', '客户 ID', '需要更新的 customerId，放在接口路径中。', true, 'cus_ysNthtSsNGrwje3', 'cus_xxx')
            ), self::customerFields(true)),
            self::customerResponseFields());
    }

    private static function customerDelete(): array
    {
        return self::api('customer-delete', 'customer', '客户', '删除客户',
            '通过 customerId 删除网关客户资料。',
            '删除客户', 'DELETE', OpenApiConstants::CUSTOMER_DELETE_PATH,
            self::fields(
                self::merchantNo(),
                self::field('customerId', '客户 ID', '需要删除的 customerId。', true, 'cus_ysNthtSsNGrwje3', 'cus_xxx')
            ),
            self::fields(
                self::field('code', 'code', '业务响应码，0 表示成功。'),
                self::field('msg', 'msg', '业务响应说明。'),
                self::field('data', 'data', 'true 表示网关已受理删除。')
            ));
    }

    private static function customerList(): array
    {
        return self::api('customer-list', 'customer', '客户', '列出客户',
            '查询当前商户下的客户列表。',
            '查询列表', 'GET', OpenApiConstants::CUSTOMER_LIST_PATH,
            self::fields(self::merchantNo()),
            self::customerResponseFields());
    }

    private static function api(string $code, string $groupCode, string $groupName, string $name, string $description, string $actionLabel, string $method, string $path, array $requestFields, array $responseFields): array
    {
        return compact('code', 'groupCode', 'groupName', 'name', 'description', 'actionLabel', 'method', 'path', 'requestFields', 'responseFields');
    }

    private static function field(string $name, string $label, string $description, bool $required = false, string $default = '', string $placeholder = ''): array
    {
        return compact('name', 'label', 'description', 'required', 'default', 'placeholder') + ['type' => 'input', 'options' => []];
    }

    private static function json(string $name, string $label, string $description, bool $required, string $default): array
    {
        return compact('name', 'label', 'description', 'required', 'default') + ['type' => 'textarea', 'placeholder' => '', 'options' => []];
    }

    private static function select(string $name, string $label, string $description, bool $required, string $default, array $options): array
    {
        return compact('name', 'label', 'description', 'required', 'default', 'options') + ['type' => 'select', 'placeholder' => ''];
    }

    private static function merchantNo(): array
    {
        return self::field('merchantNo', '商户号', '从 config/merchant-config.php 自动读取，只用于页面核对。');
    }

    private static function customerMode(): array
    {
        return self::select('customerMode', '客户提交方式', '代收创建时 customerId 和 customer 二选一，切换后页面只提交选中的字段。', true, 'customer', [
            ['value' => 'customer', 'label' => '使用客户资料 customer'],
            ['value' => 'customerId', 'label' => '使用客户 ID customerId'],
        ]);
    }

    private static function customerId(): array
    {
        return self::field('customerId', '客户 ID', '选择客户 ID 模式时提交，和客户资料二选一。', false, 'cus_ysNthtSsNGrwje3', 'cus_xxx');
    }

    private static function customerJson(): array
    {
        return self::json('customer', '客户资料', '网关要求提供 customerId 或 customer，Demo 默认提交客户资料。', true, self::CUSTOMER_JSON);
    }

    private static function currencySelect(string $description = 'ISO 4217 三位币种代码。', bool $required = true): array
    {
        return self::select('currency', '币种', $description, $required, 'USD', [
            ['value' => 'USD', 'label' => 'USD - 美元'],
            ['value' => 'EUR', 'label' => 'EUR - 欧元'],
            ['value' => 'GBP', 'label' => 'GBP - 英镑'],
            ['value' => 'CNY', 'label' => 'CNY - 人民币'],
        ]);
    }

    private static function paymentMethodOptions(): array
    {
        return [
            ['value' => 'CARD', 'label' => 'CARD - 信用卡'],
            ['value' => 'CASHAPP', 'label' => 'CASHAPP - Cash App'],
            ['value' => 'PAY_PAL', 'label' => 'PAY_PAL - PayPal'],
            ['value' => 'ACH_DEBIT', 'label' => 'ACH_DEBIT - ACH 直接借记'],
            ['value' => 'UPI', 'label' => 'UPI - 印度 UPI'],
        ];
    }

    private static function customerRequestFields(bool $update): array
    {
        return array_merge(self::fields(self::merchantNo()), self::customerFields($update));
    }

    private static function customerFields(bool $update): array
    {
        return self::fields(
            self::field('firstname', '名', '客户名。', true, $update ? 'ABC' : 'Lily'),
            self::field('lastname', '姓', '客户姓。', true, 'Brown'),
            self::field('email', '邮箱', '客户邮箱，Demo 会自动生成唯一邮箱。', true, $update ? 'AUTO_EMAIL:abc_brown_' : 'AUTO_EMAIL:lily_brown_'),
            self::field('phone', '电话', '客户联系电话。', false, $update ? '13628173753' : '13628173752'),
            self::field('identityType', '证件类型', '证件类型，例如 PASSPORT。', false, 'PASSPORT'),
            self::field('identityNo', '证件号', '客户证件号，Demo 会自动生成后缀。', false, 'AUTO:P'),
            self::field('country', '国家', 'ISO 3166-1 alpha-2 国家代码。', true, 'US', 'US'),
            self::field('state', '州/省', '州或省。', false, $update ? 'NY' : 'CA'),
            self::field('city', '城市', '城市。', false, $update ? 'New York' : 'Los Angeles'),
            self::field('address', '地址', '客户地址。', false, $update ? '456 Broadway' : '123 Main St, Apt 4B'),
            self::field('zipcode', '邮编', '邮政编码。', false, $update ? '10001' : '90001')
        );
    }

    private static function paymentResponseFields(): array
    {
        return self::fields(
            self::field('code', 'code', '业务响应码，0 表示成功。'),
            self::field('msg', 'msg', '业务响应说明。'),
            self::field('data.tradeNo', 'tradeNo', '平台代收交易流水号，用于查询和退款。'),
            self::field('data.orderNo', 'orderNo', '商户订单号。'),
            self::field('data.status', 'status', '网关交易状态数字。'),
            self::field('data.redirectUrl', 'redirectUrl', '收银台跳转地址。'),
            self::field('data.clientSecret', 'clientSecret', '客户端继续支付需要的密钥。')
        );
    }

    private static function refundResponseFields(): array
    {
        return self::fields(
            self::field('code', 'code', '业务响应码，0 表示成功。'),
            self::field('msg', 'msg', '业务响应说明。'),
            self::field('data.charge', 'charge', '退款标识，可用于查询退款。'),
            self::field('data.tradeNo', 'tradeNo', '原代收交易号。'),
            self::field('data.status', 'status', '退款状态数字。'),
            self::field('data.refundAmount', 'refundAmount', '退款金额。')
        );
    }

    private static function payoutResponseFields(): array
    {
        return self::fields(
            self::field('code', 'code', '业务响应码，0 表示成功。'),
            self::field('msg', 'msg', '业务响应说明。'),
            self::field('data.tradeNo', 'tradeNo', '平台代付交易流水号。'),
            self::field('data.orderNo', 'orderNo', '商户代付订单号。'),
            self::field('data.status', 'status', '代付状态数字。'),
            self::field('data.message', 'message', '网关代付状态说明。')
        );
    }

    private static function balanceResponseFields(): array
    {
        return self::fields(
            self::field('code', 'code', '业务响应码，0 表示成功。'),
            self::field('msg', 'msg', '业务响应说明。'),
            self::field('data[].merNo', 'merNo', '平台商户号。'),
            self::field('data[].currency', 'currency', '账户币种。'),
            self::field('data[].balance', 'balance', '可用余额。'),
            self::field('data[].frozenAmounts', 'frozenAmounts', '冻结金额。')
        );
    }

    private static function customerResponseFields(): array
    {
        return self::fields(
            self::field('code', 'code', '业务响应码，0 表示成功。'),
            self::field('msg', 'msg', '业务响应说明。'),
            self::field('data.customerId', 'customerId', '网关客户 ID。'),
            self::field('data.firstname', 'firstname', '客户名。'),
            self::field('data.lastname', 'lastname', '客户姓。'),
            self::field('data.email', 'email', '客户邮箱。')
        );
    }

    private static function fields(array ...$fields): array
    {
        return $fields;
    }

    private static function resolveDefault(string $default, string $merchantNo, string $name): string
    {
        if ($name === 'merchantNo') {
            return $merchantNo;
        }
        if (strpos($default, 'AUTO_EMAIL:') === 0) {
            return substr($default, strlen('AUTO_EMAIL:')) . OrderNoGenerator::generate('') . '@test.com';
        }
        if (strpos($default, 'AUTO:') === 0) {
            return OrderNoGenerator::generate(substr($default, strlen('AUTO:')));
        }
        return $default;
    }

    private static function pretty(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
