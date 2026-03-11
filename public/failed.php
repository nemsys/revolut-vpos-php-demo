<?php
/**
 * Payment failure page.
 * Displayed when a payment fails or is cancelled.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Revolut Demo</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Revolut Virtual POS Demo</h1>
        </div>
    </header>
    <main class="container">
        <div class="result-card failure">
            <h2>Payment Failed</h2>
            <p>Unfortunately, your payment could not be processed.</p>
            <p>Please try again or use a different payment method.</p>
            <a href="/" class="btn btn-pay">Try Again</a>
        </div>
    </main>
</body>
</html>
