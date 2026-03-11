<?php

/**
 * API Endpoint: GET /api/config.php
 *
 * Returns the public configuration needed by the frontend
 * (public key and environment mode).
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Config;

header('Content-Type: application/json');

try {
    $config = Config::getInstance();

    echo json_encode([
        'publicKey' => $config->get('public_key'),
        'environment' => $config->get('environment'),
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load configuration']);
}
