<?php
/**
 * PHP Built-in Server Router
 *
 * Routes requests to the appropriate PHP files when using
 * `php -S localhost:8080 router.php` from the project root.
 *
 * URL routing:
 *   /                     → public/index.php
 *   /success.php          → public/success.php
 *   /failed.php           → public/failed.php
 *   /css/*                → public/css/*
 *   /js/*                 → public/js/*
 *   /src/Api/*            → src/Api/*
 *   /src/Webhook/*        → src/Webhook/*
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static assets from public directory
$publicFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    // Serve static files with correct MIME type
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile($publicFile);
        return true;
    }

    // PHP files in public
    if ($ext === 'php') {
        require $publicFile;
        return true;
    }

    return false;
}

// API endpoints
if (str_starts_with($uri, '/src/Api/') || str_starts_with($uri, '/src/Webhook/')) {
    $apiFile = __DIR__ . $uri;
    if (file_exists($apiFile)) {
        require $apiFile;
        return true;
    }

    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    return true;
}

// Default: serve the main page
require __DIR__ . '/public/index.php';
return true;
