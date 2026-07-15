# Payment Gateway PHP SDK

商户服务端 PHP SDK，用于对接 Payment Gateway OpenAPI。SDK 会按当前网关最新协议生成 `Authorization: Bearer <jwt>`，POST 请求发送 `livemode + data` 加密外壳，并自动解密响应 `data`。

> 本 SDK 只能用于商户服务端。禁止放在浏览器、移动端 App、桌面客户端或任何会暴露 API 私钥、RSA 私钥、卡号、CVC 的环境。

## 环境要求

- PHP 7.4+
- Composer
- PHP 扩展：`curl`、`json`、`openssl`
- 依赖：`phpseclib/phpseclib`，用于严格实现 `RSA-OAEP-256`

## 引入 SDK

### 方式一：GitHub VCS 安装（当前推荐）

当前 SDK 尚未发布到 Packagist，商户不能只执行 `composer require wikerx/payment-gateway-php-sdk`。请先在商户项目根目录把 GitHub 仓库加入 Composer VCS 源，再安装 SDK：

```bash
composer config repositories.payment-gateway-php-sdk vcs https://github.com/wikerx/payment-gateway-php-sdk.git
composer require wikerx/payment-gateway-php-sdk:dev-main
```

也可以手动修改商户项目的 `composer.json`：

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/wikerx/payment-gateway-php-sdk.git"
    }
  ],
  "require": {
    "wikerx/payment-gateway-php-sdk": "dev-main@dev"
  }
}
```

修改后执行：

```bash
composer update wikerx/payment-gateway-php-sdk
```

安装完成后，在业务代码中引入 Composer autoload：

```php
require_once __DIR__ . '/vendor/autoload.php';
```

### 方式二：Packagist 发布后安装

等 SDK 发布到 Packagist 后，商户才可以直接执行：

```bash
composer require wikerx/payment-gateway-php-sdk
```

发布到 Packagist 之前，请使用上面的 GitHub VCS 安装方式。

### 方式三：直接运行 SDK examples

如果是平台内部或 SDK 开发者直接克隆当前仓库调试 examples，在 SDK 根目录执行：

```bash
composer install
```

安装完成后会生成 `vendor/autoload.php`，`examples` 目录下的真实网关 demo 才能运行。

本地 `path repository` 只建议 SDK 开发者在自己机器上调试，不建议写进商户接入文档。

## 配置

默认配置文件：

```text
config/merchant-config.php
```

当前示例配置使用测试商户 `2606177036`，默认请求：

```text
http://192.168.2.114:58060
```

配置完整示例：

```php
return [
    'base_url' => 'http://192.168.2.114:58060',
    'merchant_no' => '2606177036',
    'livemode' => false,
    'api_private_key' => '<merchant-api-private-key>',
    'debug_raw_log_enabled' => true,
    'platform_request_public_key_path' => __DIR__ . '/../keys/2606177036_PLATFORM_REQUEST_PUBLIC_KEY.pem',
    'merchant_response_private_key_path' => __DIR__ . '/../keys/2606177036_MERCHANT_RESPONSE_PRIVATE_KEY.pem',
];
```

字段说明：

| 配置项 | 必填 | 说明 |
|---|---:|---|
| `base_url` | 是 | 网关 OpenAPI 地址，例如 `http://192.168.2.114:58060` |
| `merchant_no` | 是 | 商户号，测试商户为 `2606177036` |
| `livemode` | 是 | `false` 请求沙盒/测试数据源，`true` 请求生产数据源 |
| `api_private_key` | 是 | JWT HS256 签名使用的商户 API 私钥 |
| `debug_raw_log_enabled` | 否 | 是否输出请求明文、密文、响应密文、响应明文等调试日志 |
| `platform_request_public_key_path` | 二选一 | 平台请求公钥 PEM 文件路径，SDK 用于加密请求 data |
| `merchant_response_private_key_path` | 二选一 | 商户响应私钥 PEM 文件路径，SDK 用于解密响应 data |
| `platform_request_public_key` | 二选一 | 平台请求公钥文本，支持 PEM 或 DER Base64 |
| `merchant_response_private_key` | 二选一 | 商户响应私钥文本，支持 PEM 或 DER Base64 |

PEM 文件模式：

```php
'platform_request_public_key_path' => __DIR__ . '/../keys/2606177036_PLATFORM_REQUEST_PUBLIC_KEY.pem',
'merchant_response_private_key_path' => __DIR__ . '/../keys/2606177036_MERCHANT_RESPONSE_PRIVATE_KEY.pem',
```

文本密钥模式：

```php
'platform_request_public_key' => '<平台请求公钥 Base64 或 PEM>',
'merchant_response_private_key' => '<商户响应私钥 Base64 或 PEM>',
```

文件配置优先于文本配置。如果要切换为文本密钥，请注释 `*_path` 配置，再打开文本密钥配置。

`debug_raw_log_enabled=true` 会输出：

- 请求地址
- 请求头
- 请求原始明文报文
- 请求参数拆分：`protectedHeader`、`header`、`encryptedAesKey`、`iv`、`cipherText`、`tag`
- 请求密文参数
- 响应原始密文参数
- 响应参数拆分
- 响应原始明文参数

生产环境建议关闭该开关。

## 创建客户端

```php
use Scott\Payment\Sdk\OpenApiClient;

$client = OpenApiClient::create(__DIR__ . '/config/merchant-config.php');
```

也可以显式加载配置：

```php
use Scott\Payment\Sdk\Config\MerchantConfigLoader;
use Scott\Payment\Sdk\OpenApiClient;

$config = MerchantConfigLoader::load(__DIR__ . '/config/merchant-config.php');
$client = new OpenApiClient($config);
```

## 真实网关 Demo

真正会请求网关的 demo 都在 `examples/api` 目录下：

| Demo | 说明 |
|---|---|
| `examples/api/inquiry/balance/FundAccountsBalanceInquiry.php` | 检索余额，只读查询 |
| `examples/api/payin/PayinCheckoutPayment.php` | 收银台代收 |
| `examples/api/payin/PayinDirectPayment.php` | 本地支付直连，示例使用 `payType=1` 和 `paymentMethod=CASHAPP` |
| `examples/api/payin/PayinTradePaymentInquiry.php` | 检索代收交易 |
| `examples/api/payin/refund/PayinRefundCreate.php` | 创建退款 |
| `examples/api/payin/refund/PayinRefundInquiry.php` | 检索退款 |
| `examples/api/payout/PayoutTradeTransfer.php` | 创建代付 |
| `examples/api/payout/PayoutTradeTransferInquiry.php` | 检索代付 |
| `examples/api/payout/PayoutTradeTransferCancel.php` | 取消代付 |
| `examples/api/customers/CustomerCreate.php` | 创建客户 |
| `examples/api/customers/CustomerUpdate.php` | 更新客户，示例会先创建前置客户 |
| `examples/api/customers/CustomerRetrieve.php` | 检索客户，示例会先创建前置客户 |
| `examples/api/customers/CustomerDelete.php` | 删除客户，示例会先创建前置客户 |
| `examples/api/customers/CustomerList.php` | 列出所有客户 |

运行示例：

```bash
php examples/api/inquiry/balance/FundAccountsBalanceInquiry.php
php examples/api/payin/PayinCheckoutPayment.php
php examples/api/payin/PayinDirectPayment.php
php examples/api/payout/PayoutTradeTransfer.php
```

按模块运行：

```bash
# 余额，只读查询
php examples/api/inquiry/balance/FundAccountsBalanceInquiry.php

# 代收
php examples/api/payin/PayinCheckoutPayment.php
php examples/api/payin/PayinDirectPayment.php
php examples/api/payin/PayinTradePaymentInquiry.php

# 退款
php examples/api/payin/refund/PayinRefundCreate.php
php examples/api/payin/refund/PayinRefundInquiry.php

# 代付
php examples/api/payout/PayoutTradeTransfer.php
php examples/api/payout/PayoutTradeTransferInquiry.php
php examples/api/payout/PayoutTradeTransferCancel.php

# 客户
php examples/api/customers/CustomerCreate.php
php examples/api/customers/CustomerUpdate.php
php examples/api/customers/CustomerRetrieve.php
php examples/api/customers/CustomerDelete.php
php examples/api/customers/CustomerList.php
```

运行真实 demo 前请确认：

- 已执行 `composer install`；
- `config/merchant-config.php` 中 `base_url` 可以访问；
- 本地网关服务已启动在 `http://192.168.2.114:58060`，或已改成真实测试环境地址；
- `livemode=false` 与测试商户、测试密钥匹配；
- 如果运行退款、查询、取消示例，已把代码里的 `tradeNo`、`charge`、`orderNo` 替换为自己的测试交易标识。

退款、查询、取消示例中写死的 `tradeNo`、`charge`、`orderNo` 只是沙盒示例值。商户联调时应替换为自己上一步接口返回的真实标识。
客户更新、检索、删除示例会先创建一个沙盒客户作为前置数据，方便商户直接运行单个 demo。

如果看到类似错误：

```text
OpenAPI HTTP request failed: Failed to connect to 192.168.2.114:58060
```

说明 SDK 已经完成 JWT、请求加密和 HTTP 请求准备，但 `base_url` 指向的网关地址不可连接。请先启动本地支付网关服务，或把 `config/merchant-config.php` 中的 `base_url` 改为可访问的沙盒网关地址。

## 页面联调控制台

SDK 示例内置轻量 PHP 页面联调控制台，适合平台内部和商户沙盒联调使用。启动方式：

```bash
composer demo
```

也可以直接使用 PHP 内置服务器：

```bash
php -S 127.0.0.1:58082 examples/demo/router.php
```

启动后访问：

```text
http://127.0.0.1:58082/demo/apis
```

控制台会按 API 文档分组展示客户、代收、退款申请、代付和余额查询接口。点击 API 后会进入参数页面，页面会自动从 `config/merchant-config.php` 读取商户号，并为订单号、邮箱、证件号等字段生成沙盒默认值。所有默认参数都可以在页面上修改，提交后由当前 PHP `OpenApiClient` 发起真实 OpenAPI 调用，并在页面下方展示请求明文、响应 JSON、关键响应字段说明和错误信息。

代收和代付创建页面已内置常用联调控件：

- `customerId` 和 `customer` 通过“客户提交方式”二选一，页面会按选择隐藏另一组字段，提交时也只组装选中的字段；
- 创建收银台代收通过下拉选择 `paymentMethodTypes`，提交后会组装为单元素数组；
- 创建直连代收和发起代付通过下拉选择币种、支付类型或支付方式；
- 切换 `paymentMethod` 时会自动替换 `paymentMethodData` 示例参数，覆盖 `CARD`、`CASHAPP`、`PAY_PAL`、`ACH_DEBIT`、`UPI`。

页面联调控制台使用真实 SDK 客户端，请求会发送到 `base_url`。发起代收、退款、代付、取消代付等操作可能创建沙盒交易或触发网关资金类业务校验；商户联调时应使用沙盒商户配置和测试网关地址。

> 页面联调控制台会读取商户号、API 私钥和 RSA 密钥配置，并允许直接发起资金类 API。只建议在本地、内网、沙盒或受控测试环境启用，不要直接暴露到公网生产环境。

## API 调用示例

### 余额查询

```php
$result = $client->retrieveBalances('USD');
var_dump($result->toArray());
```

### 收银台代收

```php
use Scott\Payment\Sdk\Support\OrderNoGenerator;

$result = $client->createCheckoutPayment([
    'orderNo' => OrderNoGenerator::generate('PAYIN_CHECKOUT_'),
    'currency' => 'USD',
    'amount' => '12.34',
    'returnUrl' => 'https://manage.forgottenthrone.com/',
    'notifyUrl' => 'http://localhost:58080/payment-sdk/api/webhook/payin',
    'customer' => [
        'firstname' => 'Lily',
        'lastname' => 'Brown',
        'email' => 'lily_brown_1782457030419@test.com',
        'country' => 'US',
    ],
]);
```

### 代付申请

```php
use Scott\Payment\Sdk\Enum\PaymentMethod;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

$result = $client->createPayout([
    'orderNo' => OrderNoGenerator::generate('PAYOUT_'),
    'currency' => 'USD',
    'amount' => '3.11',
    'paymentMethod' => PaymentMethod::CARD,
    'paymentMethodData' => [
        'number' => '4000056655665556',
        'expMonth' => '06',
        'expYear' => '2029',
        'cvc' => '123',
    ],
]);
```

### 客户创建

```php
use Scott\Payment\Sdk\Support\OrderNoGenerator;

$suffix = OrderNoGenerator::generate('CUS_');
$result = $client->createCustomer([
    'firstname' => 'Lily',
    'lastname' => 'Brown',
    'email' => 'lily_brown_' . $suffix . '@test.com',
    'phone' => '13628173752',
    'identityType' => 'PASSPORT',
    'identityNo' => 'P' . $suffix,
    'country' => 'US',
    'state' => 'CA',
    'city' => 'Los Angeles',
    'address' => '123 Main St, Apt 4B',
    'zipcode' => '90001',
]);
```

### 客户更新、检索、删除、列表

```php
$customerId = $result->getData()['customerId'];

$client->updateCustomer($customerId, [
    'firstname' => 'ABC',
    'lastname' => 'Brown',
    'email' => 'abc_brown_' . OrderNoGenerator::generate('CUS_UPD_') . '@test.com',
    'country' => 'US',
]);

$client->retrieveCustomer($customerId);

// 删除客户接口当前网关响应 data=true。
$client->deleteCustomer($customerId);

$client->listCustomers();
```

## 回调接收

示例文件：

```text
examples/webhook/payin.php
examples/webhook/payout.php
```

本地启动：

```bash
php -S 0.0.0.0:58080 -t examples/webhook
```

回调地址示例：

```text
http://localhost:58080/payin.php
http://localhost:58080/payout.php
```

直接用浏览器打开上述地址时，如果没有 `t`、`signature` 和交易参数，页面会返回 `payin/payout webhook endpoint is running, waiting gateway callback`，表示回调服务已启动并等待网关通知。只有真实网关回调或你用 curl 携带完整 Header 和参数访问时，示例才会执行验签。

示例脚本会把收到的请求头、请求参数、签名原文、期望签名、收到的签名和验签结果打印到 PHP server 日志，方便排查 `invalid signature`。

如果网关无法访问商户本机 `localhost`，请把创建交易请求中的 `notifyUrl` 改成网关可访问的内网 IP、公网域名或穿透地址。

回调处理建议：

1. 先校验 Header 中的 `t` 和 `signature`；
2. 使用 `tradeNo` 或 `orderNo` 做幂等；
3. 校验回调金额、币种、商户号是否与本地订单一致；
4. 做终态保护，避免重复通知或旧通知覆盖新状态；
5. 保存原始回调参数和验签结果，方便对账和排查；
6. 返回 `success` 前确保本地状态更新已经完成。

生产环境不要只打印回调。商户必须在验签通过后基于 `tradeNo` / `orderNo` 做幂等、金额币种核对、终态保护和本地订单状态更新。

## 本地测试

安装依赖后执行：

```bash
composer test
```

或者：

```bash
vendor/bin/phpunit --colors=always
```

语法检查：

```bash
composer lint
```

测试覆盖内容：

| 测试 | 说明 |
|---|---|
| `MerchantJwtSignerTest` | 校验 JWT header 和 claims |
| `JsonSupportTest` | 校验金额字段输出 JSON number，卡号等字段保持字符串 |
| `OpenApiLogSanitizerTest` | 校验 Authorization、卡号、CVC 脱敏，商户号不脱敏 |
| `OrderNoGeneratorTest` | 校验单进程内订单号不重复和前缀过滤 |
| `WebhookVerifierTest` | 校验 payin / payout 回调签名原文和 SHA-256 签名 |

这些测试不请求真实网关，不创建交易，不修改资金状态。

## 协议说明

- JWT：`HS256`，包含 `aud=["gateway"]`、`iss=merchant`、`jti`、`iat`、`exp`、`merchantId`、`livemode`
- 请求加密：`RSA-OAEP-256 + AES-256-GCM`
- compact payload：`protectedHeader.encryptedAesKey.iv.cipherText.tag`
- protected header：`{"typ":"PAYMENT-PAYLOAD","alg":"RSA-OAEP-256","enc":"A256GCM"}`
- GET 请求无 body，但仍携带 Bearer JWT
- POST / PUT 请求体：`{"livemode":false,"data":"compactPayload"}`
- DELETE 请求无 body，但仍携带 Bearer JWT，客户删除接口当前响应 `data=true`

## 注意事项

- `jti` 每次请求必须唯一，SDK 内部使用时间序列加随机后缀生成，不复用业务 `tradeNo`，避免多 PHP 进程同毫秒请求触发网关防重放。
- 金额建议用字符串传入，例如 `'12.34'`，避免 PHP 浮点数精度问题。
- 卡号、CVC、API 私钥、RSA 私钥不得写入普通业务日志。
- HTTP 成功不代表业务成功，商户应检查 `$result->isSuccess()`、`code` 和业务 `status`。
