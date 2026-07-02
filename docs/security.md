# 安全协议

PHP SDK 对齐 Java SDK 的 OpenAPI 安全协议：

- `Authorization: Bearer <jwt>`
- JWT 使用 HS256 和商户 API 私钥签名
- POST 请求体使用 `RSA-OAEP-256 + AES-256-GCM` 加密
- 响应 `data` 使用商户响应私钥解密
- GET 请求无 body，但仍携带 JWT 中的 `livemode`
- 回调验签使用网关当前 SHA-256 拼接规则

PHP 原生 OpenSSL 的 OAEP 加密不便显式指定 MGF1 SHA-256，因此 SDK 使用 `phpseclib/phpseclib` 实现 `RSA-OAEP-256`，避免与网关 Java 解密规则不兼容。

生产环境不要开启 `debug_raw_log_enabled`，除非短时间排障且日志访问权限已受控。
