# Payment Gateway PHP SDK

商户服务端 PHP SDK，用于对接 Payment Gateway OpenAPI。SDK 会按当前网关最新协议生成 `Authorization: Bearer <jwt>`，POST 请求发送 `livemode + data` 加密外壳，并自动解密响应 `data`。

> 本 SDK 只能用于商户服务端。禁止放在浏览器、移动端 App、桌面客户端或任何会暴露 API 私钥、RSA 私钥、卡号、CVC 的环境。

## 环境要求

- PHP 7.4+
- Composer
- PHP 扩展：`curl`、`json`、`openssl`
- 依赖：`phpseclib/phpseclib`，用于严格实现 `RSA-OAEP-256`

安装依赖：

```bash
composer install
```

## 配置

默认配置文件：

```text
config/merchant-config.php
```

当前示例配置使用测试商户 `2606177036`，默认请求：

```text
http://localhost:58060
```

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

运行示例：

```bash
php examples/api/inquiry/balance/FundAccountsBalanceInquiry.php
php examples/api/payin/PayinCheckoutPayment.php
php examples/api/payin/PayinDirectPayment.php
php examples/api/payout/PayoutTradeTransfer.php
```

退款、查询、取消示例中写死的 `tradeNo`、`charge`、`orderNo` 只是沙盒示例值。商户联调时应替换为自己上一步接口返回的真实标识。

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

生产环境不要只打印回调。商户必须在验签通过后基于 `tradeNo` / `orderNo` 做幂等、金额币种核对、终态保护和本地订单状态更新。

## 协议说明

- JWT：`HS256`，包含 `aud=["gateway"]`、`iss=merchant`、`jti`、`iat`、`exp`、`merchantId`、`livemode`
- 请求加密：`RSA-OAEP-256 + AES-256-GCM`
- compact payload：`protectedHeader.encryptedAesKey.iv.cipherText.tag`
- protected header：`{"typ":"PAYMENT-PAYLOAD","alg":"RSA-OAEP-256","enc":"A256GCM"}`
- GET 请求无 body，但仍携带 Bearer JWT
- POST 请求体：`{"livemode":false,"data":"compactPayload"}`

## 注意事项

- `jti` 每次请求必须唯一，SDK 内部使用订单号生成器生成，不复用业务 `tradeNo`。
- 金额建议用字符串传入，例如 `'12.34'`，避免 PHP 浮点数精度问题。
- 卡号、CVC、API 私钥、RSA 私钥不得写入普通业务日志。
- HTTP 成功不代表业务成功，商户应检查 `$result->isSuccess()`、`code` 和业务 `status`。
