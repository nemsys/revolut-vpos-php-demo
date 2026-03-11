#!/usr/bin/env php
<?php

/**
 * CLI tool for managing Revolut Merchant API webhooks.
 *
 * Usage:
 *   php scripts/manage-webhooks.php create <url> [events...]
 *   php scripts/manage-webhooks.php list
 *   php scripts/manage-webhooks.php delete <webhook_id>
 *
 * Examples:
 *   php scripts/manage-webhooks.php create https://example.com/webhook
 *   php scripts/manage-webhooks.php create https://example.com/webhook ORDER_COMPLETED ORDER_AUTHORISED
 *   php scripts/manage-webhooks.php list
 *   php scripts/manage-webhooks.php delete be9b34ac-eb86-452b-9bdd-03ca3e35425f
 *
 * After creating a webhook, copy the signing_secret into your .env file
 * as REVOLUT_WEBHOOK_SECRET.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\RevolutClient;

// Default events to subscribe to
const DEFAULT_EVENTS = [
    'ORDER_COMPLETED',
    'ORDER_AUTHORISED',
    'ORDER_PAYMENT_AUTHENTICATED',
    'ORDER_CANCELLED',
    'ORDER_FAILED',
];

function usage(): void
{
    $script = basename(__FILE__);
    echo <<<USAGE

    Revolut Webhook Manager
    =======================

    Usage:
      php scripts/{$script} create <url> [event1 event2 ...]
      php scripts/{$script} list
      php scripts/{$script} delete <webhook_id>

    Commands:
      create   Register a new webhook URL with Revolut.
               Returns a signing_secret to put in your .env file.
               If no events are specified, subscribes to:
                 ORDER_COMPLETED, ORDER_AUTHORISED,
                 ORDER_PAYMENT_AUTHENTICATED, ORDER_CANCELLED, ORDER_FAILED

      list     Show all registered webhooks.

      delete   Remove a webhook by its ID.

    Examples:
      php scripts/{$script} create https://mysite.com/src/Webhook/handler.php
      php scripts/{$script} create https://abc123.ngrok.io/src/Webhook/handler.php ORDER_COMPLETED
      php scripts/{$script} list
      php scripts/{$script} delete be9b34ac-eb86-452b-9bdd-03ca3e35425f

    USAGE;
}

function main(array $argv): int
{
    if (count($argv) < 2) {
        usage();
        return 1;
    }

    $command = $argv[1];

    try {
        $config = Config::getInstance();
        $client = new RevolutClient($config);
    } catch (\Exception $e) {
        echo "Error loading config: {$e->getMessage()}\n";
        echo "Make sure your .env file exists and has REVOLUT_API_SECRET_KEY set.\n";
        return 1;
    }

    return match ($command) {
        'create' => handleCreate($client, $argv),
        'list' => handleList($client),
        'delete' => handleDelete($client, $argv),
        default => handleUnknown($command),
    };
}

function handleCreate(RevolutClient $client, array $argv): int
{
    if (count($argv) < 3) {
        echo "Error: Missing webhook URL.\n";
        echo "Usage: php scripts/manage-webhooks.php create <url> [events...]\n";
        return 1;
    }

    $url = $argv[2];

    if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'http')) {
        echo "Error: Invalid URL. Must be a valid HTTP/HTTPS URL.\n";
        return 1;
    }

    // Use custom events if provided, otherwise defaults
    $events = count($argv) > 3
        ? array_slice($argv, 3)
        : DEFAULT_EVENTS;

    echo "Creating webhook...\n";
    echo "  URL:    {$url}\n";
    echo "  Events: " . implode(', ', $events) . "\n\n";

    $result = $client->createWebhook($url, $events);

    if (!$result['success']) {
        echo "Failed to create webhook: {$result['error']}\n";
        if (isset($result['http_code'])) {
            echo "HTTP status: {$result['http_code']}\n";
        }
        return 1;
    }

    $data = $result['data'];

    echo "Webhook created successfully!\n";
    echo "==============================\n";
    echo "  ID:             {$data['id']}\n";
    echo "  URL:            {$data['url']}\n";
    echo "  Events:         " . implode(', ', $data['events']) . "\n";
    echo "  Signing Secret: {$data['signing_secret']}\n";
    echo "==============================\n\n";

    echo "Next step: Add this to your .env file:\n\n";
    echo "  REVOLUT_WEBHOOK_SECRET={$data['signing_secret']}\n\n";
    echo "Then restart your server.\n";

    return 0;
}

function handleList(RevolutClient $client): int
{
    echo "Fetching webhooks...\n\n";

    $result = $client->listWebhooks();

    if (!$result['success']) {
        echo "Failed to list webhooks: {$result['error']}\n";
        return 1;
    }

    $webhooks = $result['data'];

    if (empty($webhooks)) {
        echo "No webhooks registered.\n";
        echo "Create one with: php scripts/manage-webhooks.php create <url>\n";
        return 0;
    }

    echo "Registered webhooks:\n";
    echo str_repeat('-', 70) . "\n";

    foreach ($webhooks as $wh) {
        echo "  ID:     {$wh['id']}\n";
        echo "  URL:    {$wh['url']}\n";
        echo "  Events: " . implode(', ', $wh['events'] ?? []) . "\n";
        if (isset($wh['signing_secret'])) {
            echo "  Secret: {$wh['signing_secret']}\n";
        }
        echo str_repeat('-', 70) . "\n";
    }

    echo "\nTotal: " . count($webhooks) . " webhook(s)\n";

    return 0;
}

function handleDelete(RevolutClient $client, array $argv): int
{
    if (count($argv) < 3) {
        echo "Error: Missing webhook ID.\n";
        echo "Usage: php scripts/manage-webhooks.php delete <webhook_id>\n";
        echo "Run 'php scripts/manage-webhooks.php list' to see IDs.\n";
        return 1;
    }

    $webhookId = $argv[2];

    echo "Deleting webhook {$webhookId}...\n";

    $result = $client->deleteWebhook($webhookId);

    // 204 No Content means success for DELETE
    if ($result['success'] || ($result['http_code'] ?? 0) === 204) {
        echo "Webhook deleted successfully.\n";
        return 0;
    }

    echo "Failed to delete webhook: {$result['error']}\n";
    return 1;
}

function handleUnknown(string $command): int
{
    echo "Unknown command: {$command}\n";
    usage();
    return 1;
}

exit(main($argv));
