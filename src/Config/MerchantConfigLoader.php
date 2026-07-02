<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Config;

use Scott\Payment\Sdk\Exception\OpenApiConfigException;
use Scott\Payment\Sdk\OpenApiClientConfig;
use Scott\Payment\Sdk\OpenApiConstants;

/**
 * 商户配置加载器。
 *
 * 本类负责读取 PHP 数组形式的 merchant-config.php 并创建 OpenApiClientConfig。
 * 配置可能包含 API 私钥和 RSA 私钥，加载后不得打印完整配置内容。
 */
final class MerchantConfigLoader
{
    public static function load(?string $path = null): OpenApiClientConfig
    {
        $configPath = $path ?: self::defaultPath();
        if (!is_file($configPath)) {
            throw new OpenApiConfigException('merchant config file does not exist: ' . $configPath);
        }
        $config = require $configPath;
        if (!is_array($config)) {
            throw new OpenApiConfigException('merchant config file must return array');
        }
        return new OpenApiClientConfig($config);
    }

    private static function defaultPath(): string
    {
        $candidates = [
            getcwd() . DIRECTORY_SEPARATOR . OpenApiConstants::CONFIG_FILE_NAME,
            getcwd() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . OpenApiConstants::CONFIG_FILE_NAME,
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . OpenApiConstants::CONFIG_FILE_NAME,
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return $candidates[0];
    }
}
