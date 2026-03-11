<?php

namespace App;

/**
 * HTTP client for the Revolut Merchant API.
 *
 * Handles order creation, retrieval, and API communication
 * using PHP's cURL extension.
 */
class RevolutClient
{
    private string $apiUrl;
    private string $secretKey;
    private string $apiVersion = '2024-09-01';

    public function __construct(Config $config)
    {
        $this->apiUrl = rtrim($config->get('api_url'), '/');
        $this->secretKey = $config->get('secret_key');
    }

    /**
     * Create a new payment order.
     *
     * @param int    $amount      Amount in minor currency units (e.g., 1050 = €10.50)
     * @param string $currency    ISO 4217 currency code (e.g., "EUR", "GBP", "USD")
     * @param string $description Order description
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    public function createOrder(int $amount, string $currency, string $description): array
    {
        $payload = [
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'description' => $description,
        ];

        return $this->request('POST', '/api/orders', $payload);
    }

    /**
     * Retrieve an existing order by its ID.
     *
     * @param string $orderId Revolut order ID
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    public function getOrder(string $orderId): array
    {
        return $this->request('GET', "/api/orders/{$orderId}");
    }

    /**
     * Create a webhook to receive payment event notifications.
     *
     * @param string $url    Publicly accessible HTTPS URL to receive events
     * @param array  $events List of event types (e.g., ["ORDER_COMPLETED", "ORDER_AUTHORISED"])
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    public function createWebhook(string $url, array $events): array
    {
        return $this->request('POST', '/api/1.0/webhooks', [
            'url' => $url,
            'events' => $events,
        ]);
    }

    /**
     * List all registered webhooks.
     *
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    public function listWebhooks(): array
    {
        return $this->request('GET', '/api/1.0/webhooks');
    }

    /**
     * Delete a webhook by its ID.
     *
     * @param string $webhookId Revolut webhook ID
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    public function deleteWebhook(string $webhookId): array
    {
        return $this->request('DELETE', "/api/1.0/webhooks/{$webhookId}");
    }

    /**
     * Send an HTTP request to the Revolut Merchant API.
     *
     * @param string     $method  HTTP method (GET, POST, etc.)
     * @param string     $path    API endpoint path
     * @param array|null $payload Request body (for POST/PATCH)
     * @return array{success: bool, data?: array, error?: string, http_code?: int}
     */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        $url = $this->apiUrl . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'Revolut-Api-Version: ' . $this->apiVersion,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "cURL error: {$error}"];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $data, 'http_code' => $httpCode];
        }

        $errorMessage = $data['message'] ?? $data['description'] ?? 'Unknown API error';
        return ['success' => false, 'error' => $errorMessage, 'http_code' => $httpCode, 'data' => $data];
    }
}
