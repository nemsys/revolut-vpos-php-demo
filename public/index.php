<?php
/**
 * Main checkout page.
 *
 * Displays a product catalog with demo items. Users can click "Pay" to
 * trigger the Revolut Checkout widget (card pop-up) for payment processing.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config;

$config = Config::getInstance();
$environment = $config->get('environment');
$publicKey = $config->get('public_key');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revolut Virtual POS Demo</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Revolut Virtual POS Demo</h1>
            <p class="subtitle">PHP Integration with Sandbox Environment</p>
            <span class="badge badge-sandbox">Sandbox Mode</span>
        </div>
    </header>

    <main class="container">
        <!-- Product Cards -->
        <section class="products">
            <h2>Products</h2>
            <div class="product-grid">
                <div class="product-card" data-amount="1050" data-currency="EUR" data-name="Classic T-Shirt">
                    <div class="product-image">
                        <div class="placeholder-image">T-Shirt</div>
                    </div>
                    <div class="product-info">
                        <h3>Classic T-Shirt</h3>
                        <p class="price">&euro;10.50</p>
                        <button class="btn btn-pay" onclick="handlePayment(this)">Pay Now</button>
                    </div>
                </div>

                <div class="product-card" data-amount="2500" data-currency="EUR" data-name="Premium Mug">
                    <div class="product-image">
                        <div class="placeholder-image">Mug</div>
                    </div>
                    <div class="product-info">
                        <h3>Premium Mug</h3>
                        <p class="price">&euro;25.00</p>
                        <button class="btn btn-pay" onclick="handlePayment(this)">Pay Now</button>
                    </div>
                </div>

                <div class="product-card" data-amount="4999" data-currency="EUR" data-name="Wireless Headphones">
                    <div class="product-image">
                        <div class="placeholder-image">Headphones</div>
                    </div>
                    <div class="product-info">
                        <h3>Wireless Headphones</h3>
                        <p class="price">&euro;49.99</p>
                        <button class="btn btn-pay" onclick="handlePayment(this)">Pay Now</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Custom Amount Form -->
        <section class="custom-payment">
            <h2>Custom Payment</h2>
            <form id="custom-payment-form" onsubmit="handleCustomPayment(event)">
                <div class="form-group">
                    <label for="amount">Amount (EUR)</label>
                    <input type="number" id="amount" name="amount" min="0.01" step="0.01" value="1.00" required>
                </div>
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency">
                        <option value="EUR" selected>EUR</option>
                        <option value="GBP">GBP</option>
                        <option value="USD">USD</option>
                        <option value="BGN">BGN</option>
                        <option value="PLN">PLN</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="Custom payment" required>
                </div>
                <button type="submit" class="btn btn-pay">Pay Custom Amount</button>
            </form>
        </section>

        <!-- Notification Area -->
        <div id="notifications"></div>

        <!-- Test Card Info -->
        <section class="test-info">
            <h2>Sandbox Test Information</h2>
            <div class="info-box">
                <h3>Test Card Numbers</h3>
                <p>Use these card numbers in sandbox mode with <strong>any future expiry date</strong> and <strong>any 3-digit CVV</strong>:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Card Type</th>
                            <th>Number</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Visa</td>
                            <td><code>4929420573595709</code></td>
                            <td>Successful payment</td>
                        </tr>
                        <tr>
                            <td>Mastercard</td>
                            <td><code>2720992593319364</code></td>
                            <td>Successful payment</td>
                        </tr>
                        <tr>
                            <td>Visa</td>
                            <td><code>4000000000000077</code></td>
                            <td>Declined</td>
                        </tr>
                    </tbody>
                </table>
                <p class="note">These test cards only work in the Sandbox environment.</p>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>Revolut Virtual POS Demo &mdash; ProgressBG PHP Course</p>
            <p class="note">This is a demo application for educational purposes.</p>
        </div>
    </footer>

    <!-- Revolut Checkout SDK -->
    <?php
    $embedHost = ($environment === 'prod') ? 'merchant.revolut.com' : 'sandbox-merchant.revolut.com';
    ?>
    <script src="https://<?= $embedHost ?>/embed.js"></script>
    <!-- App Configuration (injected from PHP) -->
    <script>
        const APP_CONFIG = {
            environment: '<?= htmlspecialchars($environment, ENT_QUOTES, 'UTF-8') ?>',
            publicKey: '<?= htmlspecialchars($publicKey, ENT_QUOTES, 'UTF-8') ?>'
        };
    </script>
    <script src="/js/checkout.js"></script>
</body>
</html>
