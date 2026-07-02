<?php

declare(strict_types=1);

namespace Scott\Payment\Sdk\Webhook;

/**
 * 代收异步通知签名校验器。
 *
 * 本类按网关规则拼接 t、tradeNo、orderNo、currency、amount、status、code、message 并计算 SHA-256 hex。
 * 本类只做本地签名摘要计算和常量时间比较，不接收 HTTP、不落库、不修改资金状态。
 */
final class PayinWebhookVerifier
{
    public function verify(string $timestamp, string $signature, array $request): bool
    {
        if (trim($timestamp) === '' || trim($signature) === '') {
            return false;
        }
        return hash_equals(strtolower(trim($signature)), $this->sign($timestamp, $request));
    }

    public function sign(string $timestamp, array $request): string
    {
        return hash('sha256', $this->buildSignSource($timestamp, $request));
    }

    public function buildSignSource(string $timestamp, array $request): string
    {
        return $timestamp
            . (string)($request['tradeNo'] ?? '')
            . (string)($request['orderNo'] ?? '')
            . (string)($request['currency'] ?? '')
            . $this->amountText($request['amount'] ?? null)
            . (isset($request['status']) ? (string)$request['status'] : '')
            . (string)($request['code'] ?? '')
            . (string)($request['message'] ?? '');
    }

    private function amountText($amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }
        $text = (string)$amount;
        if (strpos($text, '.') === false) {
            return $text;
        }
        return rtrim(rtrim($text, '0'), '.');
    }
}
