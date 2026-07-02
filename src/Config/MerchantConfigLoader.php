<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Config;

use Scott\Payment\Sdk\Exception\OpenApiConfigException;
use Scott\Payment\Sdk\OpenApiClientConfig;
use Scott\Payment\Sdk\OpenApiConstants;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : MerchantConfigLoader
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 商户配置加载器，负责读取 PHP 数组形式的 merchant-config.php 并创建 OpenApiClientConfig。配置可能包含 API 私钥和 RSA 私钥，加载后不得打印完整配置内容；本类不访问网关、不修改密钥或资金状态。
 * @status : modify
 */
final class MerchantConfigLoader
{
    /**
     * 加载商户配置文件。
     *
     * 配置文件必须返回数组，且可能包含 API 私钥和 RSA 私钥；本方法不会打印完整配置，不访问网关。
     *
     * @param string|null $path 配置文件路径，传 null 时按默认位置查找。
     * @return OpenApiClientConfig 客户端配置。
     */
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

    /**
     * 查找默认 merchant-config.php 路径。
     *
     * @return string 配置文件候选路径；文件不存在时返回第一个候选路径用于错误提示。
     */
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
