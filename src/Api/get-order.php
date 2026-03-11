<?php

/**
 * API Endpoint: GET /api/get-order.php?id={orderId}
 *
 * Retrieves an existing order from the Revolut Merchant API.
 *
 * Query parameters:
 *   - id: string (Revolut order ID)
 *
 * Response (JSON):
 *   - success: bool
 *   - data: object (full order details)
 *   - error: string (only on failure)
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Config;
use App\RevolutClient;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$orderId = trim($_GET['id'] ?? '');

if (empty($orderId) || !preg_match('/^[a-f0-9\-]+$/i', $orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    $config = Config::getInstance();
    $client = new RevolutClient($config);

    $result = $client->getOrder($orderId);

    if ($result['success']) {
        echo json_encode(['success' => true, 'data' => $result['data']]);
    } else {
        http_response_code($result['http_code'] ?? 500);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
