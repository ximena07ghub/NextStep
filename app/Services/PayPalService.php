<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function accessToken(): string
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');

        if (! $clientId || ! $secret) {
            throw new RuntimeException('PayPal no está configurado.');
        }

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()->timeout(15)
            ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new RuntimeException('No se pudo autenticar con PayPal.');
        }

        return $response->json('access_token');
    }

    private function request()
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(20);
    }

    public function createOrder(string $companyId, string $membershipId, string $amount, string $returnUrl, string $cancelUrl): array
    {
        $response = $this->request()->post($this->baseUrl().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $membershipId,
                'custom_id' => $companyId,
                'amount' => ['currency_code' => 'MXN', 'value' => $amount],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW',
            ]]],
        ]);

        if (! $response->successful() || ! is_string($response->json('id'))) {
            throw new RuntimeException('No se pudo crear la orden de PayPal.');
        }

        $order = $response->json();
        $approve = collect($order['links'] ?? [])->first(fn ($link) => in_array($link['rel'] ?? '', ['payer-action', 'approve'], true));
        $url = $approve['href'] ?? null;
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        if (! is_string($url) || ! in_array($host, ['www.paypal.com', 'www.sandbox.paypal.com'], true)) {
            throw new RuntimeException('PayPal no devolvió un enlace seguro.');
        }

        return ['id' => $order['id'], 'url' => $url];
    }

    public function captureOrder(string $orderId): array
    {
        $response = $this->request()->withHeaders(['PayPal-Request-Id' => 'nextstep-'.hash('sha256', $orderId)])
            ->withBody('{}', 'application/json')
            ->post($this->baseUrl().'/v2/checkout/orders/'.rawurlencode($orderId).'/capture');

        if ($response->successful()) {
            return $response->json();
        }

        // una captura repetida puede consultarse sin volver a cobrar
        return $this->getOrder($orderId);
    }

    public function getOrder(string $orderId): array
    {
        $response = $this->request()->get($this->baseUrl().'/v2/checkout/orders/'.rawurlencode($orderId));
        if (! $response->successful()) {
            throw new RuntimeException('No se pudo verificar la orden de PayPal.');
        }

        return $response->json();
    }
}
