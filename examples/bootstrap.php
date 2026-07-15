<?php

declare(strict_types=1);

/**
 * 示例公共引导文件，负责加载 Composer autoload、创建默认 OpenApiClient 并提供测试客户资料。本文件只服务 examples 目录，不直接发起网关请求、不修改资金或交易状态。
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Scott\Payment\Sdk\Config\MerchantConfigLoader;
use Scott\Payment\Sdk\OpenApiClient;
use Scott\Payment\Sdk\Support\OrderNoGenerator;

/**
 * 运行一个真实网关示例，并把异常转换成可读的 CLI 提示。
 *
 * PHP CLI 在不同版本和 ini 配置下对全局异常处理的呈现不完全一致；示例文件显式包裹入口可以避免商户看到整屏 Fatal stack trace。
 *
 * @param callable $callback 示例主体。
 */
function run_example(callable $callback): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        handle_example_exception($exception);
    }
}

/**
 * 输出示例异常信息。
 *
 * @param Throwable $exception 示例执行异常。
 */
function handle_example_exception(Throwable $exception): void
{
    fwrite(STDERR, PHP_EOL . '示例执行失败: ' . get_class($exception) . PHP_EOL);
    fwrite(STDERR, '错误信息: ' . $exception->getMessage() . PHP_EOL);
    if (strpos($exception->getMessage(), 'Failed to connect') !== false || strpos($exception->getMessage(), 'Could not connect') !== false) {
        fwrite(STDERR, '处理建议: 请确认 config/merchant-config.php 中 base_url 可访问，并且支付网关服务已启动；当前示例默认请求 http://192.168.2.114:58060。' . PHP_EOL);
    }
    exit(1);
}

/**
 * 创建示例使用的真实 OpenAPI 客户端。
 *
 * 该方法只加载 config/merchant-config.php 并实例化 SDK 客户端，不主动访问网关、不生成 JWT、不加密报文。
 * 后续示例调用 createPayment、createCustomer、createPayout 等方法时才会真实请求网关。
 *
 * @return OpenApiClient 使用当前商户配置的 SDK 客户端。
 */
function openapi_client(): OpenApiClient
{
    return new OpenApiClient(MerchantConfigLoader::load(__DIR__ . '/../config/merchant-config.php'));
}

/**
 * 输出示例日志。
 *
 * 示例日志用于商户核对请求参数和响应参数；如果 value 中包含客户资料或支付资料，调用方应优先传入 SDK 已脱敏后的结果。
 *
 * @param string $title 日志标题。
 * @param mixed $value 日志内容。
 */
function log_result(string $title, $value): void
{
    echo $title . ': ' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
}

/**
 * 构造支付、代付示例复用的客户资料。
 *
 * 返回值包含姓名、邮箱、手机号和地址等个人信息，只用于沙盒联调示例；本方法不访问网关、不加密、不修改资金或客户状态。
 *
 * @return array 客户资料数组。
 */
function customer_info(): array
{
    return [
        'firstname' => 'Lily',
        'lastname' => 'Brown',
        'email' => 'lily_brown_1782457030419@test.com',
        'phone' => '13628173752',
        'country' => 'US',
        'state' => 'CA',
        'city' => 'Los Angeles',
        'address' => '123 Main St, Apt 4B',
        'zipcode' => '90001',
    ];
}

/**
 * 构造客户创建示例请求。
 *
 * 该请求会生成唯一邮箱和证件号，避免商户反复运行 demo 时触发客户资料重复。
 * 请求包含个人信息，真实发送前会由 OpenApiClient 加密为 compact payload。
 *
 * @return array 客户创建请求参数。
 */
function customer_create_request(): array
{
    $suffix = OrderNoGenerator::generate('CUS_');
    return [
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
    ];
}

/**
 * 构造客户更新示例请求。
 *
 * 该请求用于更新示例前置创建的沙盒客户，不负责商户本地客户同步、KYC、外部渠道同步或状态流转。
 *
 * @return array 客户更新请求参数。
 */
function customer_update_request(): array
{
    $suffix = OrderNoGenerator::generate('CUS_UPD_');
    return [
        'firstname' => 'ABC',
        'lastname' => 'Brown',
        'email' => 'abc_brown_' . $suffix . '@test.com',
        'phone' => '13628173753',
        'identityType' => 'PASSPORT',
        'identityNo' => 'P' . $suffix,
        'country' => 'US',
        'state' => 'NY',
        'city' => 'New York',
        'address' => '456 Broadway',
        'zipcode' => '10001',
    ];
}

/**
 * 为检索、更新、删除示例创建前置客户。
 *
 * 该方法会真实调用客户创建接口并返回 customerId，方便商户单独运行某一个示例文件。
 * 如果网关返回失败或未返回 customerId，会抛出异常中断后续示例，避免拿空 ID 继续请求。
 *
 * @param OpenApiClient $client SDK 客户端。
 * @return string 网关返回的客户 ID。
 */
function create_customer_for_case(OpenApiClient $client): string
{
    $result = $client->createCustomer(customer_create_request());
    $data = $result->getData();
    if (!$result->isSuccess() || !is_array($data) || empty($data['customerId'])) {
        throw new RuntimeException('创建前置客户失败: ' . json_encode($result->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    return (string)$data['customerId'];
}
