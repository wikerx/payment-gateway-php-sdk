<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

use Scott\Payment\Sdk\Exception\OpenApiConfigException;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : OpenApiClientConfig
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : OpenAPI 客户端配置，负责承载 baseUrl、商户号、livemode、JWT API 私钥、平台请求公钥和商户响应私钥等 SDK 运行参数。配置中包含敏感密钥，只允许在商户服务端内存中使用，不得输出到普通日志或返回给前端；本类不发起 HTTP 请求、不执行签名或加密。
 * @status : modify
 */
final class OpenApiClientConfig
{
    /**
     * 网关基础地址，格式为 http 或 https URL。
     *
     * 是否敏感：否。
     * 是否允许为空：否。
     */
    private string $baseUrl;

    /**
     * 商户号。
     *
     * 是否敏感：否，测试环境可明文打印。
     * 用途：参与 JWT claim，网关据此定位商户配置。
     */
    private string $merchantNo;

    /**
     * 环境标识。
     *
     * true 表示生产环境，false 表示测试环境；该字段会写入 JWT 和加密请求体。
     */
    private bool $livemode;

    /**
     * JWT API 私钥。
     *
     * 敏感字段：是。
     * 用途：商户侧签发 Bearer JWT。
     * 限制：不得输出到日志、前端或异常消息。
     */
    private string $apiPrivateKey;

    /**
     * 平台请求公钥。
     *
     * 敏感字段：否。
     * 用途：SDK 使用该公钥加密商户请求明文。
     */
    private string $platformRequestPublicKey;

    /**
     * 商户响应私钥。
     *
     * 敏感字段：是。
     * 用途：SDK 使用该私钥解密平台响应中的 AES 会话密钥。
     * 限制：不得输出到日志、前端或异常消息。
     */
    private string $merchantResponsePrivateKey;

    /**
     * 原始调试日志开关。
     *
     * 是否敏感：否。
     * 用途：测试环境排查请求明文、密文拆分和响应解密流程；生产环境建议关闭。
     */
    private bool $debugRawLogEnabled;

    /**
     * HTTP 连接超时时间，单位毫秒。
     */
    private int $connectTimeoutMs;

    /**
     * HTTP 读取超时时间，单位毫秒。
     */
    private int $readTimeoutMs;

    /**
     * 根据商户配置数组创建客户端配置。
     *
     * 构造过程会解析 PEM 文件模式或文本密钥模式，并执行必要字段校验；不会访问网关、不会签发 JWT、不会修改密钥配置。
     *
     * @param array $config merchant-config.php 返回的配置数组。
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim((string)($config['base_url'] ?? ''), '/');
        $this->merchantNo = (string)($config['merchant_no'] ?? '');
        $this->livemode = (bool)($config['livemode'] ?? false);
        $this->apiPrivateKey = (string)($config['api_private_key'] ?? '');
        $this->debugRawLogEnabled = (bool)($config['debug_raw_log_enabled'] ?? false);
        $this->connectTimeoutMs = (int)($config['connect_timeout_ms'] ?? OpenApiConstants::HTTP_CONNECT_TIMEOUT_MS);
        $this->readTimeoutMs = (int)($config['read_timeout_ms'] ?? OpenApiConstants::HTTP_READ_TIMEOUT_MS);
        $this->platformRequestPublicKey = $this->resolveKey($config, 'platform_request_public_key');
        $this->merchantResponsePrivateKey = $this->resolveKey($config, 'merchant_response_private_key');
        $this->validate();
    }

    /**
     * 获取网关基础地址。
     *
     * @return string 不带结尾斜杠的 baseUrl。
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * 获取商户号。
     *
     * @return string 商户号明文。
     */
    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    /**
     * 获取环境标识。
     *
     * @return bool true 表示生产环境，false 表示测试环境。
     */
    public function isLivemode(): bool
    {
        return $this->livemode;
    }

    /**
     * 获取 JWT API 私钥。
     *
     * 返回值为敏感字段，只能传入 JWT 签名器，不得写入普通日志。
     *
     * @return string JWT API 私钥。
     */
    public function getApiPrivateKey(): string
    {
        return $this->apiPrivateKey;
    }

    /**
     * 获取平台请求公钥。
     *
     * @return string PEM 内容或 Base64 DER 文本，用于请求报文加密。
     */
    public function getPlatformRequestPublicKey(): string
    {
        return $this->platformRequestPublicKey;
    }

    /**
     * 获取商户响应私钥。
     *
     * 返回值为敏感字段，只能用于响应报文解密，不得写入普通日志。
     *
     * @return string PEM 内容或 Base64 DER 文本，用于响应报文解密。
     */
    public function getMerchantResponsePrivateKey(): string
    {
        return $this->merchantResponsePrivateKey;
    }

    /**
     * 判断是否开启原始报文调试日志。
     *
     * @return bool true 表示输出便于沙盒联调的明文、密文和拆分参数日志。
     */
    public function isDebugRawLogEnabled(): bool
    {
        return $this->debugRawLogEnabled;
    }

    /**
     * 获取 HTTP 连接超时时间。
     *
     * @return int 超时时间，单位毫秒。
     */
    public function getConnectTimeoutMs(): int
    {
        return $this->connectTimeoutMs;
    }

    /**
     * 获取 HTTP 读取超时时间。
     *
     * @return int 超时时间，单位毫秒。
     */
    public function getReadTimeoutMs(): int
    {
        return $this->readTimeoutMs;
    }

    /**
     * 解析 RSA 密钥配置。
     *
     * 文件路径配置优先于文本配置，便于商户在 PEM 文件模式和文本密钥模式之间切换。返回内容可能包含完整密钥材料，不得输出到普通日志。
     *
     * @param array $config 配置数组。
     * @param string $name 密钥配置名前缀。
     * @return string PEM 或 Base64 密钥文本。
     */
    private function resolveKey(array $config, string $name): string
    {
        $pathKey = $name . '_path';
        if (!empty($config[$pathKey])) {
            $path = (string)$config[$pathKey];
            if (!is_file($path)) {
                throw new OpenApiConfigException($pathKey . ' file does not exist');
            }
            $content = file_get_contents($path);
            if ($content === false || trim($content) === '') {
                throw new OpenApiConfigException($pathKey . ' file is empty');
            }
            return $content;
        }
        return (string)($config[$name] ?? '');
    }

    /**
     * 校验 SDK 启动所需配置。
     *
     * 该校验只检查配置完整性和基础格式，不验证商户号与密钥是否在网关侧绑定；绑定关系由网关鉴权阶段校验。
     */
    private function validate(): void
    {
        if ($this->baseUrl === '' || !preg_match('/^https?:\/\//', $this->baseUrl)) {
            throw new OpenApiConfigException('base_url must be a valid http or https URL');
        }
        if ($this->merchantNo === '') {
            throw new OpenApiConfigException('merchant_no can not be blank');
        }
        if ($this->apiPrivateKey === '') {
            throw new OpenApiConfigException('api_private_key can not be blank');
        }
        if (strlen($this->apiPrivateKey) < 32) {
            throw new OpenApiConfigException('api_private_key must be at least 256 bits for HS256');
        }
        if (trim($this->platformRequestPublicKey) === '') {
            throw new OpenApiConfigException('platform request public key can not be blank');
        }
        if (trim($this->merchantResponsePrivateKey) === '') {
            throw new OpenApiConfigException('merchant response private key can not be blank');
        }
    }
}
