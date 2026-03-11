<?php
/**
 * PHP Unit Tests for Revolut Demo Project
 *
 * Tests:
 * - Config loading
 * - RevolutClient instantiation
 * - WebhookHandler signature verification
 * - API endpoint validation
 *
 * Run: php tests/test-server.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\RevolutClient;
use App\WebhookHandler;

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $testName): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  PASS: {$testName}\n";
        $passed++;
    } else {
        echo "  FAIL: {$testName}\n";
        $failed++;
    }
}

function assert_equals($expected, $actual, string $testName): void
{
    assert_true($expected === $actual, "{$testName} (expected: " . var_export($expected, true) . ", got: " . var_export($actual, true) . ")");
}

echo "=== Revolut Demo Project Tests ===\n\n";

// Test 1: Config Loading
echo "[Config]\n";
try {
    $config = Config::getInstance();
    assert_true($config instanceof Config, 'Config singleton created');
    assert_true(!empty($config->get('api_url')), 'API URL is set');
    assert_true(!empty($config->get('public_key')), 'Public key is set');
    assert_true(!empty($config->get('secret_key')), 'Secret key is set');
    assert_true(
        in_array($config->get('environment'), ['sandbox', 'prod']),
        'Environment is valid (sandbox or prod)'
    );
    assert_true(
        str_starts_with($config->get('api_url'), 'https://'),
        'API URL uses HTTPS'
    );
} catch (\Exception $e) {
    echo "  FAIL: Config loading threw exception: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// Test 2: RevolutClient
echo "[RevolutClient]\n";
try {
    $client = new RevolutClient($config);
    assert_true($client instanceof RevolutClient, 'RevolutClient instantiated');
} catch (\Exception $e) {
    echo "  FAIL: RevolutClient threw exception: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// Test 3: WebhookHandler
echo "[WebhookHandler]\n";
try {
    $handler = new WebhookHandler($config);
    assert_true($handler instanceof WebhookHandler, 'WebhookHandler instantiated');

    // Test invalid signature
    $result = $handler->verifySignature('test', '', '');
    assert_equals(false, $result, 'Empty signature rejected');

    $result = $handler->verifySignature('test', 'v1=invalid', '0');
    assert_equals(false, $result, 'Invalid timestamp rejected');

    // Test event processing
    $result = $handler->processEvent([
        'event' => 'ORDER_COMPLETED',
        'order_id' => 'test-123',
    ]);
    assert_equals('ORDER_COMPLETED', $result['event'], 'Event type extracted');
    assert_equals('test-123', $result['order_id'], 'Order ID extracted');
    assert_true(str_contains($result['message'], 'completed'), 'Success message generated');

    $result = $handler->processEvent([
        'event' => 'ORDER_FAILED',
        'order_id' => 'test-456',
    ]);
    assert_true(str_contains($result['message'], 'failed'), 'Failure message generated');
} catch (\Exception $e) {
    echo "  FAIL: WebhookHandler threw exception: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// Test 4: Router file exists and is valid PHP
echo "[Router]\n";
$routerFile = __DIR__ . '/../router.php';
assert_true(file_exists($routerFile), 'router.php exists');
$routerContent = file_get_contents($routerFile);
assert_true(str_contains($routerContent, 'REQUEST_URI'), 'Router handles REQUEST_URI');

echo "\n";

// Test 5: Public files exist
echo "[Public Files]\n";
assert_true(file_exists(__DIR__ . '/../public/index.php'), 'index.php exists');
assert_true(file_exists(__DIR__ . '/../public/success.php'), 'success.php exists');
assert_true(file_exists(__DIR__ . '/../public/failed.php'), 'failed.php exists');
assert_true(file_exists(__DIR__ . '/../public/css/style.css'), 'style.css exists');
assert_true(file_exists(__DIR__ . '/../public/js/checkout.js'), 'checkout.js exists');

echo "\n";

// Test 6: API files exist and have correct structure
echo "[API Files]\n";
$createOrderFile = __DIR__ . '/../src/Api/create-order.php';
assert_true(file_exists($createOrderFile), 'create-order.php exists');
$content = file_get_contents($createOrderFile);
assert_true(str_contains($content, 'POST'), 'create-order validates POST method');
assert_true(str_contains($content, 'json_encode'), 'create-order returns JSON');
assert_true(str_contains($content, 'filter_var'), 'create-order validates input');

$getOrderFile = __DIR__ . '/../src/Api/get-order.php';
assert_true(file_exists($getOrderFile), 'get-order.php exists');

$configFile = __DIR__ . '/../src/Api/config.php';
assert_true(file_exists($configFile), 'config.php API exists');

echo "\n";

// Test 7: Webhook handler file
echo "[Webhook Files]\n";
$webhookFile = __DIR__ . '/../src/Webhook/handler.php';
assert_true(file_exists($webhookFile), 'webhook handler.php exists');
$content = file_get_contents($webhookFile);
assert_true(str_contains($content, 'verifySignature'), 'Webhook verifies signature');

echo "\n";

// Test 8: JavaScript checkout file
echo "[JavaScript]\n";
$jsContent = file_get_contents(__DIR__ . '/../public/js/checkout.js');
assert_true(str_contains($jsContent, 'RevolutCheckout'), 'JS initializes RevolutCheckout');
assert_true(str_contains($jsContent, 'payWithPopup'), 'JS uses payWithPopup');
assert_true(str_contains($jsContent, 'onSuccess'), 'JS handles onSuccess');
assert_true(str_contains($jsContent, 'onError'), 'JS handles onError');

echo "\n";

// Summary
echo "=================================\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo "=================================\n";

exit($failed > 0 ? 1 : 0);
