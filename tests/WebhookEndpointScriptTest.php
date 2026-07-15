<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Scott\Payment\Sdk\Webhook\PayinWebhookVerifier;
use Scott\Payment\Sdk\Webhook\PayoutWebhookVerifier;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : WebhookEndpointScriptTest
 * @date : 2026-07-15 20:15
 * @email : scott_x@163.com
 * @description : PHP webhook 示例入口测试，负责验证浏览器空访问不会被误判为验签失败，并验证带签名 query 参数的 payin/payout 示例可正常返回 success。本测试不启动真实网关、不落库、不修改资金状态。
 * @status : modify
 */
final class WebhookEndpointScriptTest extends TestCase
{
    /**
     * @var resource|null PHP 内置服务器进程。
     */
    private static $serverProcess;

    /**
     * PHP 内置服务器端口。
     */
    private static int $serverPort = 0;

    /**
     * PHP 内置服务器日志文件。
     */
    private static string $serverLogFile = '';

    /**
     * 启动 PHP 内置服务器，使用真实 HTTP 请求测试 examples/webhook 入口。
     */
    public static function setUpBeforeClass(): void
    {
        self::$serverPort = self::findFreePort();
        self::$serverLogFile = sys_get_temp_dir() . '/payment-gateway-php-sdk-webhook-' . self::$serverPort . '.log';
        $docRoot = realpath(__DIR__ . '/../examples/webhook');
        if ($docRoot === false) {
            self::fail('examples/webhook directory not found');
        }

        $command = PHP_BINARY . ' -S 127.0.0.1:' . self::$serverPort . ' -t ' . escapeshellarg($docRoot);
        self::$serverProcess = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['file', self::$serverLogFile, 'a'],
            2 => ['file', self::$serverLogFile, 'a'],
        ], $pipes);
        if (!is_resource(self::$serverProcess)) {
            self::markTestSkipped('Cannot start PHP built-in server for webhook endpoint tests');
        }
        fclose($pipes[0]);
        self::waitServerReady(self::$serverPort);
    }

    /**
     * 停止 PHP 内置服务器。
     */
    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }
    }

    /**
     * 验证代收回调入口空访问时返回探活提示，而不是 invalid signature。
     */
    public function testPayinEndpointShouldReturnHealthTextForEmptyProbe(): void
    {
        [$statusCode, $body] = $this->request('/payin.php');

        self::assertSame(200, $statusCode);
        self::assertSame('payin webhook endpoint is running, waiting gateway callback', $body);
    }

    /**
     * 验证代付回调入口空访问时返回探活提示，而不是 invalid signature。
     */
    public function testPayoutEndpointShouldReturnHealthTextForEmptyProbe(): void
    {
        [$statusCode, $body] = $this->request('/payout.php');

        self::assertSame(200, $statusCode);
        self::assertSame('payout webhook endpoint is running, waiting gateway callback', $body);
    }

    /**
     * 验证代收回调入口可以处理带签名的 query 参数。
     */
    public function testPayinEndpointShouldAcceptSignedQuery(): void
    {
        $params = [
            'merNo' => '2607039255',
            'tradeNo' => 'pay_202607151832120212391',
            'orderNo' => 'PAYIN_202607151832009826',
            'currency' => 'USD',
            'amount' => '19.00',
            'paymentMethod' => 'PAY_PAL',
            'status' => '3',
            'code' => 'fail',
            'message' => 'Fail',
            'metadata' => 'myParam=1',
        ];
        $timestamp = '1784111725000';
        $signature = (new PayinWebhookVerifier())->sign($timestamp, $params);

        [$statusCode, $body] = $this->request('/payin.php?' . http_build_query($params), [
            't: ' . $timestamp,
            'signature: ' . $signature,
        ]);

        self::assertSame(200, $statusCode);
        self::assertSame('success', $body);
    }

    /**
     * 验证代付回调入口可以处理带签名的 query 参数。
     */
    public function testPayoutEndpointShouldAcceptSignedQuery(): void
    {
        $params = [
            'merNo' => '2607039255',
            'tradeNo' => 'payout_202607151832120212391',
            'orderNo' => 'dfu202607151832009826',
            'currency' => 'USD',
            'amount' => '19.00',
            'paymentMethod' => 'PAY_PAL',
            'completionDate' => '2026-07-15T10:35:25',
            'status' => '3',
            'code' => 'fail',
            'message' => 'Fail',
            'metadata' => 'myParam=1',
        ];
        $timestamp = '1784111725000';
        $signature = (new PayoutWebhookVerifier())->sign($timestamp, $params);

        [$statusCode, $body] = $this->request('/payout.php?' . http_build_query($params), [
            't: ' . $timestamp,
            'signature: ' . $signature,
        ]);

        self::assertSame(200, $statusCode);
        self::assertSame('success', $body);
    }

    /**
     * 请求本地 PHP 内置服务器并返回 HTTP 状态码和响应体。
     *
     * @param string $path 请求路径，包含 query string。
     * @param array $headers 请求头。
     * @return array{0: int, 1: string} [HTTP 状态码, 响应体]
     */
    private function request(string $path, array $headers = []): array
    {
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ],
        ]);
        $body = file_get_contents('http://127.0.0.1:' . self::$serverPort . $path, false, $context);
        if ($body === false) {
            self::fail('Webhook endpoint request failed. Server log: ' . @file_get_contents(self::$serverLogFile));
        }

        $statusCode = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $statusCode = (int)$matches[1];
                break;
            }
        }

        return [$statusCode, $body];
    }

    /**
     * 获取本机空闲端口。
     */
    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            self::fail('Cannot allocate a free port for webhook endpoint tests');
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        if ($name === false || strpos($name, ':') === false) {
            self::fail('Cannot detect allocated port for webhook endpoint tests');
        }
        return (int)substr(strrchr($name, ':'), 1);
    }

    /**
     * 等待 PHP 内置服务器启动完成。
     */
    private static function waitServerReady(int $port): void
    {
        $deadline = microtime(true) + 5;
        do {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if (is_resource($socket)) {
                fclose($socket);
                return;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        self::fail('PHP built-in server did not start. Server log: ' . @file_get_contents(self::$serverLogFile));
    }
}
