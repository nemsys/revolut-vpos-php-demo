/**
 * Revolut Checkout Integration - Client-side JavaScript
 *
 * Handles:
 * - Creating orders via the PHP backend API
 * - Initializing the Revolut Checkout widget (card pop-up)
 * - Processing payment success/failure callbacks
 * - UI notifications
 */

/**
 * Show a notification toast message.
 * @param {string} message - The message to display
 * @param {'success'|'error'|'info'} type - Notification type
 */
function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    container.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

/**
 * Create an order on the backend, which calls the Revolut Merchant API.
 * @param {number} amount - Amount in minor units (cents)
 * @param {string} currency - ISO 4217 currency code
 * @param {string} description - Order description
 * @returns {Promise<{success: boolean, token?: string, order_id?: string, error?: string}>}
 */
async function createOrder(amount, currency, description) {
    const response = await fetch('/src/Api/create-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount, currency, description }),
    });

    return response.json();
}

/**
 * Handle payment for a product card button click.
 * @param {HTMLButtonElement} button - The clicked pay button
 */
async function handlePayment(button) {
    const card = button.closest('.product-card');
    const amount = parseInt(card.dataset.amount, 10);
    const currency = card.dataset.currency;
    const name = card.dataset.name;

    await processPayment(amount, currency, name, button);
}

/**
 * Handle the custom payment form submission.
 * @param {Event} event - Form submit event
 */
async function handleCustomPayment(event) {
    event.preventDefault();

    const form = event.target;
    const amountMajor = parseFloat(form.querySelector('#amount').value);
    const currency = form.querySelector('#currency').value;
    const description = form.querySelector('#description').value;

    // Convert to minor units (cents)
    const amount = Math.round(amountMajor * 100);

    if (amount <= 0) {
        showNotification('Please enter a valid amount', 'error');
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    await processPayment(amount, currency, description, button);
}

/**
 * Core payment processing flow:
 * 1. Create order on backend
 * 2. Initialize Revolut Checkout widget
 * 3. Handle payment result
 *
 * @param {number} amount - Amount in minor units
 * @param {string} currency - Currency code
 * @param {string} description - Order description
 * @param {HTMLButtonElement} button - Button to disable during processing
 */
async function processPayment(amount, currency, description, button) {
    // Disable button during processing
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Processing...';

    try {
        showNotification('Creating order...', 'info');

        // Step 1: Create order via backend API
        const order = await createOrder(amount, currency, description);

        if (!order.success) {
            showNotification(`Order creation failed: ${order.error}`, 'error');
            return;
        }

        showNotification('Order created. Opening payment widget...', 'info');

        // Step 2: Initialize Revolut Checkout with the order token
        const { payWithPopup } = await RevolutCheckout(
            order.token,
            APP_CONFIG.environment  // 'sandbox' or 'prod'
        );

        // Step 3: Open the card payment pop-up
        payWithPopup({
            onSuccess() {
                showNotification('Payment successful!', 'success');
                // Redirect to success page after a short delay
                setTimeout(() => {
                    window.location.href = '/success.php';
                }, 1500);
            },
            onError(error) {
                showNotification(`Payment failed: ${error.message || 'Unknown error'}`, 'error');
            },
            onCancel() {
                showNotification('Payment cancelled', 'info');
            },
        });
    } catch (error) {
        showNotification(`Error: ${error.message}`, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}
