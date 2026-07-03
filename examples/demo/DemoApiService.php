<?php

declare(strict_types=1);

use Scott\Payment\Sdk\Config\MerchantConfigLoader;
use Scott\Payment\Sdk\OpenApiClient;
use Scott\Payment\Sdk\OpenApiClientConfig;
use Scott\Payment\Sdk\OpenApiResult;

final class DemoApiService
{
    private OpenApiClientConfig $config;

    public function __construct()
    {
        $this->config = MerchantConfigLoader::load(__DIR__ . '/../../config/merchant-config.php');
    }

    public function config(): OpenApiClientConfig
    {
        return $this->config;
    }

    public function invoke(array $definition, array $params): array
    {
        $requestForDisplay = null;
        $bufferStarted = false;
        try {
            $client = new OpenApiClient($this->config);
            ob_start();
            $bufferStarted = true;
            switch ($definition['code']) {
                case 'payin-checkout':
                    $requestForDisplay = $this->checkoutPaymentRequest($params);
                    $result = $client->createCheckoutPayment($requestForDisplay);
                    break;
                case 'payin-direct':
                    $requestForDisplay = $this->localPaymentRequest($params);
                    $result = $client->createLocalPayment($requestForDisplay);
                    break;
                case 'payin-retrieve':
                    $requestForDisplay = ['tradeNo' => $this->text($params, 'tradeNo')];
                    $result = $client->retrievePayment($this->required($params, 'tradeNo'));
                    break;
                case 'refund-create':
                    $requestForDisplay = $this->refundCreateRequest($params);
                    $result = $client->createRefund($requestForDisplay);
                    break;
                case 'refund-retrieve':
                    $requestForDisplay = ['refundNo' => $this->text($params, 'refundNo')];
                    $result = $client->retrieveRefund($this->required($params, 'refundNo'));
                    break;
                case 'payout-create':
                    $requestForDisplay = $this->payoutCreateRequest($params);
                    $result = $client->createPayout($requestForDisplay);
                    break;
                case 'payout-retrieve':
                    $requestForDisplay = ['tradeNo' => $this->text($params, 'tradeNo')];
                    $result = $client->retrievePayout($this->required($params, 'tradeNo'));
                    break;
                case 'payout-cancel':
                    $requestForDisplay = $this->payoutCancelRequest($params);
                    $result = $client->cancelPayout($requestForDisplay);
                    break;
                case 'balance-retrieve':
                    $requestForDisplay = ['currency' => $this->text($params, 'currency')];
                    $result = $this->text($params, 'currency') === ''
                        ? $client->retrieveBalances()
                        : $client->retrieveBalances($this->text($params, 'currency'));
                    break;
                case 'customer-create':
                    $requestForDisplay = $this->customerCreateRequest($params);
                    $result = $client->createCustomer($requestForDisplay);
                    break;
                case 'customer-retrieve':
                    $requestForDisplay = ['customerId' => $this->text($params, 'customerId')];
                    $result = $client->retrieveCustomer($this->required($params, 'customerId'));
                    break;
                case 'customer-update':
                    $requestForDisplay = $this->customerCreateRequest($params);
                    $result = $client->updateCustomer($this->required($params, 'customerId'), $requestForDisplay);
                    break;
                case 'customer-delete':
                    $requestForDisplay = ['customerId' => $this->text($params, 'customerId')];
                    $result = $client->deleteCustomer($this->required($params, 'customerId'));
                    break;
                case 'customer-list':
                    $requestForDisplay = ['merchantNo' => $this->config->getMerchantNo()];
                    $result = $client->listCustomers();
                    break;
                default:
                    throw new InvalidArgumentException('Unsupported demo api: ' . $definition['code']);
            }
            ob_end_clean();
            $bufferStarted = false;

            return [
                'success' => true,
                'summary' => $this->summary($result),
                'requestJson' => $this->pretty($requestForDisplay),
                'responseJson' => $this->pretty($result->toArray()),
                'errorMessage' => '',
            ];
        } catch (Throwable $exception) {
            if ($bufferStarted) {
                ob_end_clean();
            }
            return [
                'success' => false,
                'summary' => '调用失败: ' . get_class($exception),
                'requestJson' => $this->pretty($requestForDisplay ?? $params),
                'responseJson' => '',
                'errorMessage' => $exception->getMessage(),
            ];
        }
    }

    private function checkoutPaymentRequest(array $params): array
    {
        $request = [
            'orderNo' => $this->required($params, 'orderNo'),
            'currency' => $this->required($params, 'currency'),
            'amount' => $this->required($params, 'amount'),
            'returnUrl' => $this->optional($params, 'returnUrl'),
            'notifyUrl' => $this->optional($params, 'notifyUrl'),
            'clientIp' => $this->optional($params, 'clientIp'),
            'website' => $this->optional($params, 'website'),
            'metadata' => $this->optional($params, 'metadata'),
            'paymentMethodTypes' => [$this->required($params, 'paymentMethodTypes')],
        ];
        $this->applyPayinCustomer($request, $params);
        return $this->clean($request);
    }

    private function localPaymentRequest(array $params): array
    {
        $request = [
            'orderNo' => $this->required($params, 'orderNo'),
            'payType' => (int)$this->required($params, 'payType'),
            'currency' => $this->required($params, 'currency'),
            'amount' => $this->required($params, 'amount'),
            'paymentMethod' => $this->required($params, 'paymentMethod'),
            'paymentMethodData' => $this->jsonArray($params, 'paymentMethodData'),
            'notifyUrl' => $this->optional($params, 'notifyUrl'),
            'clientIp' => $this->optional($params, 'clientIp'),
            'website' => $this->optional($params, 'website'),
            'metadata' => $this->optional($params, 'metadata'),
        ];
        $this->applyPayinCustomer($request, $params);
        return $this->clean($request);
    }

    private function refundCreateRequest(array $params): array
    {
        return $this->clean([
            'tradeNo' => $this->required($params, 'tradeNo'),
            'currency' => $this->required($params, 'currency'),
            'amount' => $this->required($params, 'amount'),
            'refundAmount' => $this->required($params, 'refundAmount'),
            'refundReason' => $this->required($params, 'refundReason'),
            'metadata' => $this->optional($params, 'metadata'),
        ]);
    }

    private function payoutCreateRequest(array $params): array
    {
        return $this->clean([
            'orderNo' => $this->required($params, 'orderNo'),
            'currency' => $this->required($params, 'currency'),
            'amount' => $this->required($params, 'amount'),
            'paymentMethod' => $this->required($params, 'paymentMethod'),
            'paymentMethodData' => $this->jsonArray($params, 'paymentMethodData'),
            'notifyUrl' => $this->optional($params, 'notifyUrl'),
            'clientIp' => $this->optional($params, 'clientIp'),
            'website' => $this->optional($params, 'website'),
            'metadata' => $this->optional($params, 'metadata'),
            'customer' => $this->jsonArray($params, 'customer'),
        ]);
    }

    private function payoutCancelRequest(array $params): array
    {
        return $this->clean([
            'tradeNo' => $this->required($params, 'tradeNo'),
            'orderNo' => $this->required($params, 'orderNo'),
            'remark' => $this->optional($params, 'remark'),
        ]);
    }

    private function customerCreateRequest(array $params): array
    {
        return $this->clean([
            'firstname' => $this->required($params, 'firstname'),
            'lastname' => $this->required($params, 'lastname'),
            'email' => $this->required($params, 'email'),
            'phone' => $this->optional($params, 'phone'),
            'identityType' => $this->optional($params, 'identityType'),
            'identityNo' => $this->optional($params, 'identityNo'),
            'country' => $this->required($params, 'country'),
            'state' => $this->optional($params, 'state'),
            'city' => $this->optional($params, 'city'),
            'address' => $this->optional($params, 'address'),
            'zipcode' => $this->optional($params, 'zipcode'),
        ]);
    }

    private function applyPayinCustomer(array &$request, array $params): void
    {
        if ($this->text($params, 'customerMode') === 'customerId') {
            $request['customerId'] = $this->required($params, 'customerId');
            unset($request['customer']);
            return;
        }
        $request['customer'] = $this->jsonArray($params, 'customer');
        unset($request['customerId']);
    }

    private function summary(OpenApiResult $result): string
    {
        return $result->isSuccess()
            ? '调用完成，网关业务 code=0'
            : '调用完成，网关返回业务失败 code=' . $result->getCode();
    }

    private function jsonArray(array $params, string $name): array
    {
        $value = $this->required($params, $name);
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new InvalidArgumentException($name . ' must be a JSON object');
        }
        return $decoded;
    }

    private function required(array $params, string $name): string
    {
        $value = $this->text($params, $name);
        if ($value === '') {
            throw new InvalidArgumentException('Missing required field: ' . $name);
        }
        return $value;
    }

    private function optional(array $params, string $name): ?string
    {
        $value = $this->text($params, $name);
        return $value === '' ? null : $value;
    }

    private function text(array $params, string $name): string
    {
        $value = $params[$name] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    private function clean(array $request): array
    {
        return array_filter($request, static function ($value): bool {
            return $value !== null && $value !== '';
        });
    }

    private function pretty($value): string
    {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        return $json === false ? (string)$value : $json;
    }
}
