<?php

/**
 * Webhook Endpoint: POST /webhook/handler.php
 *
 * Receives and processes payment event notifications from Revolut.
 * Verifies the webhook signature before processing.
 *
 * Events handled:
 *   - ORDER_COMPLETED: Payment successfully completed
 *   - ORDER_AUTHORISED: Payment authorized (pending capture)
 *   - ORDER_CANCELLED: Order was cancelled
 *   - ORDER_FAILED: Payment failed
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Config;
use App\WebhookHandler;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$revolutSignature = $_SERVER['HTTP_REVOLUT_SIGNATURE'] ?? '';
$revolutTimestamp = $_SERVER['HTTP_REVOLUT_REQUEST_TIMESTAMP'] ?? '';

try {
    $config = Config::getInstance();
    $handler = new WebhookHandler($config);

    // Verify signature
    if (!$handler->verifySignature($rawBody, $revolutSignature, $revolutTimestamp)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }

    // Process the event
    $payload = json_decode($rawBody, true);
    if (!$payload) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }

    $result = $handler->processEvent($payload);

    // Return 200 quickly to acknowledge receipt
    echo json_encode(['status' => 'ok', 'message' => $result['message']]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
}
