<?php

namespace App;

/**
 * Handles incoming webhook notifications from Revolut.
 *
 * Verifies webhook signatures and processes payment events
 * (ORDER_COMPLETED, ORDER_AUTHORISED, ORDER_CANCELLED, etc.).
 */
class WebhookHandler
{
    private string $webhookSecret;
    private const TIMESTAMP_TOLERANCE = 300000; // 5 minutes in milliseconds

    public function __construct(Config $config)
    {
        $this->webhookSecret = $config->get('webhook_secret');
    }

    /**
     * Verify the webhook payload signature.
     *
     * @param string $rawBody              Raw request body
     * @param string $revolutSignature     Value of Revolut-Signature header
     * @param string $revolutTimestamp     Value of Revolut-Request-Timestamp header
     * @return bool
     */
    public function verifySignature(string $rawBody, string $revolutSignature, string $revolutTimestamp): bool
    {
        if (empty($this->webhookSecret) || empty($revolutSignature) || empty($revolutTimestamp)) {
            return false;
        }

        // Validate timestamp is within tolerance
        $currentTimestamp = (int)(microtime(true) * 1000);
        $difference = $currentTimestamp - (int)$revolutTimestamp;
        if ($difference < 0 || $difference > self::TIMESTAMP_TOLERANCE) {
            return false;
        }

        // Extract signature version (e.g., "v1")
        $equalsPos = strpos($revolutSignature, '=');
        if ($equalsPos === false) {
            return false;
        }
        $signatureVersion = substr($revolutSignature, 0, $equalsPos);

        // Build the payload to sign: version.timestamp.body
        $payloadToSign = "{$signatureVersion}.{$revolutTimestamp}.{$rawBody}";

        // Calculate expected HMAC
        $expectedSignature = $signatureVersion . '=' . hash_hmac('sha256', $payloadToSign, $this->webhookSecret);

        return hash_equals($expectedSignature, $revolutSignature);
    }

    /**
     * Process a verified webhook event.
     *
     * @param array $payload Decoded webhook payload
     * @return array{event: string, order_id: string, message: string}
     */
    public function processEvent(array $payload): array
    {
        $event = $payload['event'] ?? 'UNKNOWN';
        $orderId = $payload['order_id'] ?? 'N/A';

        $message = match ($event) {
            'ORDER_COMPLETED' => "Payment completed for order {$orderId}",
            'ORDER_AUTHORISED' => "Payment authorised for order {$orderId}",
            'ORDER_PAYMENT_AUTHENTICATED' => "Payment authenticated for order {$orderId}",
            'ORDER_CANCELLED' => "Order {$orderId} was cancelled",
            'ORDER_FAILED' => "Payment failed for order {$orderId}",
            default => "Received event {$event} for order {$orderId}",
        };

        // Log the event
        $logEntry = date('Y-m-d H:i:s') . " | {$event} | Order: {$orderId}\n";
        $logFile = dirname(__DIR__) . '/logs/webhooks.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        return ['event' => $event, 'order_id' => $orderId, 'message' => $message];
    }
}
