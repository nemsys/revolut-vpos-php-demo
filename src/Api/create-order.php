<?php

/**
 * API Endpoint: POST /api/create-order.php
 *
 * Creates a new payment order via the Revolut Merchant API.
 * Returns the order token needed to initialize the checkout widget.
 *
 * Request body (JSON):
 *   - amount: int (in minor currency units, e.g., 1050 = €10.50)
 *   - currency: string (ISO 4217 code, e.g., "EUR")
 *   - description: string
 *
 * Response (JSON):
 *   - success: bool
 *   - token: string (Revolut public order ID for the widget)
 *   - order_id: string (Revolut order ID)
 *   - error: string (only on failure)
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Config;
use App\RevolutClient;

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Parse JSON request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
    exit;
}

// Validate required fields
$amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_INT);
$currency = trim($input['currency'] ?? '');
$description = trim($input['description'] ?? '');

if (!$amount || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid amount']);
    exit;
}

$allowedCurrencies = ['EUR', 'GBP', 'USD', 'BGN', 'PLN', 'CZK', 'RON', 'SEK', 'NOK', 'DKK', 'CHF'];
if (!in_array(strtoupper($currency), $allowedCurrencies)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported currency. Allowed: ' . implode(', ', $allowedCurrencies)]);
    exit;
}

if (empty($description)) {
    $description = 'Payment order';
}

try {
    $config = Config::getInstance();
    $client = new RevolutClient($config);

    $result = $client->createOrder($amount, $currency, $description);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'token' => $result['data']['token'] ?? $result['data']['public_id'] ?? '',
            'order_id' => $result['data']['id'] ?? '',
            'state' => $result['data']['state'] ?? '',
        ]);
    } else {
        http_response_code($result['http_code'] ?? 500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
        ]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
