# 签名算法与报文加密

本文说明 PHP SDK 当前实现的 OpenAPI 协议，商户如需自研其他语言 SDK，可按本流程对齐。

## JWT 签名

请求 Header：

```text
Authorization: Bearer {jwt}
Accept: application/json
Content-Type: application/json; charset=UTF-8
X-Request-Id: {uuid}
User-Agent: payment-gateway-php-sdk/0.1.0 php
```

JWT Header：

```json
{"typ":"JWT","alg":"HS256"}
```

JWT Claims：

```json
{
  "aud": ["gateway"],
  "iss": "merchant",
  "jti": "BALANCE_QUERY_20260702153025987000",
  "iat": 1782813258,
  "exp": 1782813438,
  "merchantId": "2606177036",
  "livemode": false
}
```

`jti` 是防重放字段，每次请求必须唯一。SDK 内部使用 `OrderNoGenerator` 生成，不复用 `tradeNo`、`orderNo` 或 `charge`。

## 请求报文加密

POST 请求业务明文先序列化为 JSON，再加密为 compact payload。

protected header：

```json
{"typ":"PAYMENT-PAYLOAD","alg":"RSA-OAEP-256","enc":"A256GCM"}
```

compact payload：

```text
base64url(header).base64url(encryptedAesKey).base64url(iv).base64url(cipherText).base64url(tag)
```

算法规则：

- AES key：随机 32 字节
- IV：随机 12 字节
- AES：`AES-256-GCM`
- GCM tag：16 字节
- AAD：compact payload 第一段 protected header
- RSA：`RSA-OAEP-256`，OAEP hash 和 MGF1 hash 都是 SHA-256

最终 POST 请求体：

```json
{
  "livemode": false,
  "data": "{compactPayload}"
}
```

## 响应解密

网关响应外壳：

```json
{
  "code": 0,
  "msg": "",
  "livemode": false,
  "data": "{compactPayload}"
}
```

SDK 使用商户响应私钥解密 `data`，并校验响应 `livemode` 与本地配置一致。

## 回调验签

代收回调签名原文：

```text
t + tradeNo + orderNo + currency + amount + status + code + message
```

代付回调签名原文：

```text
t + tradeNo + currency + amount + status + code + message
```

签名算法：

```text
lowercase_hex(sha256(signSource))
```

金额字段必须使用网关回调中的原始字符串参与验签，不能做数值化、四舍五入或裁剪尾随 0。
例如网关回调 URL 中是 `amount=19.00`，签名原文就必须拼接 `19.00`，不能改成 `19`。
