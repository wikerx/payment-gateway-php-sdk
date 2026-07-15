<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Webhook;

/**
 * @author : scott
 * @version : v1.0.0
 * @classname : PayoutWebhookVerifier
 * @date : 2026-07-02 17:30
 * @email : scott_x@163.com
 * @description : 代付异步通知签名校验器，负责按网关规则拼接 t、tradeNo、currency、amount、status、code、message 并计算 SHA-256 hex。本类只做本地签名摘要计算和常量时间比较，不接收 HTTP、不落库、不修改资金、不推进交易状态。
 * @status : modify
 */
final class PayoutWebhookVerifier
{
    /**
     * 校验代付异步通知签名。
     *
     * 该方法不修改任何业务状态；签名不匹配时返回 false，由商户 Controller 决定 HTTP 响应。
     *
     * @param string $timestamp Header t，网关生成签名时使用的毫秒时间戳。
     * @param string $signature Header signature，网关传入的 SHA-256 hex。
     * @param array $request 代付回调参数。
     * @return bool true 表示签名一致。
     */
    public function verify(string $timestamp, string $signature, array $request): bool
    {
        if (trim($timestamp) === '' || trim($signature) === '') {
            return false;
        }
        return hash_equals(strtolower(trim($signature)), $this->sign($timestamp, $request));
    }

    /**
     * 计算代付异步通知签名。
     *
     * @param string $timestamp Header t。
     * @param array $request 代付回调参数。
     * @return string SHA-256 hex 小写签名。
     */
    public function sign(string $timestamp, array $request): string
    {
        return hash('sha256', $this->buildSignSource($timestamp, $request));
    }

    /**
     * 构建代付异步通知签名原文。
     *
     * 签名原文为 t + tradeNo + currency + amount + status + code + message。
     *
     * @param string $timestamp Header t。
     * @param array $request 代付回调参数。
     * @return string 签名原文。
     */
    public function buildSignSource(string $timestamp, array $request): string
    {
        return $timestamp
            . (string)($request['tradeNo'] ?? '')
            . (string)($request['currency'] ?? '')
            . $this->text($request['amount'] ?? null)
            . (isset($request['status']) ? (string)$request['status'] : '')
            . (string)($request['code'] ?? '')
            . (string)($request['message'] ?? '');
    }

    private function text($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return (string)$value;
    }
}
