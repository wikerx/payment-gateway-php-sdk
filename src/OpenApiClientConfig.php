<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk;

use Scott\Payment\Sdk\Exception\OpenApiConfigException;

/**
 * OpenAPI 客户端配置。
 *
 * 本类负责承载 baseUrl、商户号、livemode、JWT API 私钥、平台请求公钥和商户响应私钥等 SDK 运行参数。
 * 配置中包含敏感密钥，只允许在商户服务端内存中使用，不得输出到普通日志或返回给前端。
 */
final class OpenApiClientConfig
{
    private string $baseUrl;
    private string $merchantNo;
    private bool $livemode;
    private string $apiPrivateKey;
    private string $platformRequestPublicKey;
    private string $merchantResponsePrivateKey;
    private bool $debugRawLogEnabled;
    private int $connectTimeoutMs;
    private int $readTimeoutMs;

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

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getMerchantNo(): string
    {
        return $this->merchantNo;
    }

    public function isLivemode(): bool
    {
        return $this->livemode;
    }

    public function getApiPrivateKey(): string
    {
        return $this->apiPrivateKey;
    }

    public function getPlatformRequestPublicKey(): string
    {
        return $this->platformRequestPublicKey;
    }

    public function getMerchantResponsePrivateKey(): string
    {
        return $this->merchantResponsePrivateKey;
    }

    public function isDebugRawLogEnabled(): bool
    {
        return $this->debugRawLogEnabled;
    }

    public function getConnectTimeoutMs(): int
    {
        return $this->connectTimeoutMs;
    }

    public function getReadTimeoutMs(): int
    {
        return $this->readTimeoutMs;
    }

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
