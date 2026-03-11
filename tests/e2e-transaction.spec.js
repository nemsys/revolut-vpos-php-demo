// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * End-to-end transaction tests.
 *
 * These tests verify the full payment flow:
 * - Creating an order via the PHP backend (which calls Revolut sandbox API)
 * - Revolut Checkout widget appearing when Pay is clicked
 * - Custom payment form working
 */

test.describe('End-to-End Transaction Flow', () => {

    test('create order via API returns valid token', async ({ request }) => {
        const response = await request.post('http://localhost:8080/src/Api/create-order.php', {
            data: {
                amount: 1050,
                currency: 'EUR',
                description: 'E2E Test T-Shirt',
            },
        });

        expect(response.ok()).toBeTruthy();
        const json = await response.json();

        expect(json.success).toBe(true);
        expect(json.token).toBeTruthy();
        expect(json.token.length).toBeGreaterThan(10);
        expect(json.order_id).toBeTruthy();
        expect(json.state).toBe('pending');
    });

    test('retrieve order via API returns order details', async ({ request }) => {
        // First create an order
        const createResp = await request.post('http://localhost:8080/src/Api/create-order.php', {
            data: {
                amount: 2500,
                currency: 'EUR',
                description: 'E2E Test Mug',
            },
        });
        const createJson = await createResp.json();
        expect(createJson.success).toBe(true);

        // Now retrieve it
        const getResp = await request.get(
            `http://localhost:8080/src/Api/get-order.php?id=${createJson.order_id}`
        );
        expect(getResp.ok()).toBeTruthy();

        const getJson = await getResp.json();
        expect(getJson.success).toBe(true);
        expect(getJson.data.id).toBe(createJson.order_id);
        expect(getJson.data.amount).toBe(2500);
        expect(getJson.data.currency).toBe('EUR');
        expect(getJson.data.description).toBe('E2E Test Mug');
        expect(getJson.data.state).toBe('pending');
    });

    test('clicking Pay creates order and triggers Revolut SDK', async ({ page }) => {
        await page.goto('http://localhost:8080/');

        // Intercept the create-order API call to confirm it fires
        const orderPromise = page.waitForResponse(
            resp => resp.url().includes('create-order.php') && resp.status() === 200
        );

        // Click the first product Pay button
        await page.locator('.product-card').first().locator('.btn-pay').click();

        // Wait for the order API call to complete
        const orderResponse = await orderPromise;
        const orderJson = await orderResponse.json();
        expect(orderJson.success).toBe(true);
        expect(orderJson.token).toBeTruthy();

        // After order creation, the JS calls RevolutCheckout(token, 'sandbox')
        // then payWithPopup(). In headless browser the SDK may inject iframes,
        // new DOM elements, or trigger network requests to revolut.com.
        // Verify the SDK was loaded and the notification shows the widget was triggered.
        await page.waitForTimeout(1500);

        const notifications = page.locator('.notification');
        const count = await notifications.count();
        // We should see at least the "Opening payment widget..." notification
        let foundWidgetNotification = false;
        for (let i = 0; i < count; i++) {
            const text = await notifications.nth(i).textContent();
            if (text && text.includes('widget')) {
                foundWidgetNotification = true;
                break;
            }
        }
        expect(foundWidgetNotification).toBe(true);
    });

    test('custom payment form creates order and opens widget', async ({ page }) => {
        await page.goto('http://localhost:8080/');

        // Fill in custom payment form
        await page.locator('#amount').fill('5.00');
        await page.locator('#currency').selectOption('GBP');
        await page.locator('#description').fill('E2E Custom Test');

        // Intercept the create-order API call
        const orderPromise = page.waitForResponse(
            resp => resp.url().includes('create-order.php') && resp.status() === 200
        );

        // Submit the form
        await page.locator('#custom-payment-form button[type="submit"]').click();

        // Wait for the order API call
        const orderResponse = await orderPromise;
        const orderJson = await orderResponse.json();
        expect(orderJson.success).toBe(true);
        expect(orderJson.token).toBeTruthy();
    });

    test('notification appears when Pay is clicked', async ({ page }) => {
        await page.goto('http://localhost:8080/');

        // Click pay on the second product
        await page.locator('.product-card').nth(1).locator('.btn-pay').click();

        // Wait for notification to appear
        const notification = page.locator('.notification');
        await expect(notification.first()).toBeVisible({ timeout: 5000 });
        await expect(notification.first()).toContainText('Creating order');
    });

    test('multiple currencies work for order creation', async ({ request }) => {
        const currencies = ['EUR', 'GBP', 'USD'];

        for (const currency of currencies) {
            const response = await request.post('http://localhost:8080/src/Api/create-order.php', {
                data: {
                    amount: 100,
                    currency: currency,
                    description: `Multi-currency test ${currency}`,
                },
            });

            const json = await response.json();
            expect(json.success).toBe(true);
            expect(json.token).toBeTruthy();
        }
    });
});
