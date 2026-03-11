// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Revolut Virtual POS Demo', () => {
    test('homepage loads correctly', async ({ page }) => {
        await page.goto('/');

        // Check page title
        await expect(page).toHaveTitle('Revolut Virtual POS Demo');

        // Check header
        await expect(page.locator('header h1')).toHaveText('Revolut Virtual POS Demo');
        await expect(page.locator('.badge-sandbox')).toHaveText('Sandbox Mode');
    });

    test('displays product cards', async ({ page }) => {
        await page.goto('/');

        // Check that 3 product cards are rendered
        const cards = page.locator('.product-card');
        await expect(cards).toHaveCount(3);

        // Check product names
        await expect(cards.nth(0).locator('h3')).toHaveText('Classic T-Shirt');
        await expect(cards.nth(1).locator('h3')).toHaveText('Premium Mug');
        await expect(cards.nth(2).locator('h3')).toHaveText('Wireless Headphones');

        // Check prices are displayed
        await expect(cards.nth(0).locator('.price')).toContainText('10.50');
        await expect(cards.nth(1).locator('.price')).toContainText('25.00');
        await expect(cards.nth(2).locator('.price')).toContainText('49.99');
    });

    test('product cards have pay buttons', async ({ page }) => {
        await page.goto('/');

        const payButtons = page.locator('.product-card .btn-pay');
        await expect(payButtons).toHaveCount(3);

        for (let i = 0; i < 3; i++) {
            await expect(payButtons.nth(i)).toHaveText('Pay Now');
            await expect(payButtons.nth(i)).toBeEnabled();
        }
    });

    test('custom payment form is present', async ({ page }) => {
        await page.goto('/');

        // Check form exists
        const form = page.locator('#custom-payment-form');
        await expect(form).toBeVisible();

        // Check form fields
        await expect(page.locator('#amount')).toBeVisible();
        await expect(page.locator('#currency')).toBeVisible();
        await expect(page.locator('#description')).toBeVisible();

        // Check default values
        await expect(page.locator('#amount')).toHaveValue('1.00');
        await expect(page.locator('#currency')).toHaveValue('EUR');
        await expect(page.locator('#description')).toHaveValue('Custom payment');
    });

    test('currency selector has correct options', async ({ page }) => {
        await page.goto('/');

        const options = page.locator('#currency option');
        await expect(options).toHaveCount(5);

        const expectedCurrencies = ['EUR', 'GBP', 'USD', 'BGN', 'PLN'];
        for (let i = 0; i < expectedCurrencies.length; i++) {
            await expect(options.nth(i)).toHaveAttribute('value', expectedCurrencies[i]);
        }
    });

    test('test card information section is displayed', async ({ page }) => {
        await page.goto('/');

        const testInfo = page.locator('.test-info');
        await expect(testInfo).toBeVisible();
        await expect(testInfo.locator('h2')).toHaveText('Sandbox Test Information');

        // Check test card table
        const rows = testInfo.locator('tbody tr');
        await expect(rows).toHaveCount(3);

        // Verify Visa test card number is shown
        await expect(rows.nth(0).locator('code')).toHaveText('4929420573595709');
    });

    test('data attributes on product cards are correct', async ({ page }) => {
        await page.goto('/');

        const cards = page.locator('.product-card');

        // T-Shirt: 1050 cents = €10.50
        await expect(cards.nth(0)).toHaveAttribute('data-amount', '1050');
        await expect(cards.nth(0)).toHaveAttribute('data-currency', 'EUR');
        await expect(cards.nth(0)).toHaveAttribute('data-name', 'Classic T-Shirt');

        // Mug: 2500 cents = €25.00
        await expect(cards.nth(1)).toHaveAttribute('data-amount', '2500');

        // Headphones: 4999 cents = €49.99
        await expect(cards.nth(2)).toHaveAttribute('data-amount', '4999');
    });

    test('Revolut Checkout SDK script is loaded', async ({ page }) => {
        await page.goto('/');

        // Check that the Revolut SDK script tag exists
        const sdkScript = page.locator('script[src*="merchant.revolut.com/embed.js"]');
        await expect(sdkScript).toHaveCount(1);
    });

    test('APP_CONFIG is injected in page source', async ({ page }) => {
        await page.goto('/');

        // Verify the inline script containing APP_CONFIG is rendered in the HTML
        const scriptContent = await page.locator('script:not([src])').first().innerHTML();
        expect(scriptContent).toContain('APP_CONFIG');
        expect(scriptContent).toContain("environment: 'sandbox'");
    });

    test('success page renders', async ({ page }) => {
        await page.goto('/success.php');

        await expect(page).toHaveTitle(/Payment Successful/);
        await expect(page.locator('.result-card.success')).toBeVisible();
        await expect(page.locator('.result-card.success h2')).toHaveText('Payment Successful!');

        // Back to shop button exists
        const backBtn = page.locator('.result-card a.btn');
        await expect(backBtn).toHaveAttribute('href', '/');
    });

    test('failed page renders', async ({ page }) => {
        await page.goto('/failed.php');

        await expect(page).toHaveTitle(/Payment Failed/);
        await expect(page.locator('.result-card.failure')).toBeVisible();
        await expect(page.locator('.result-card.failure h2')).toHaveText('Payment Failed');
    });

    test('API config endpoint returns correct data', async ({ page }) => {
        const response = await page.request.get('/src/Api/config.php');
        expect(response.ok()).toBeTruthy();

        const data = await response.json();
        expect(data.publicKey).toBeDefined();
        expect(data.environment).toBe('sandbox');
    });

    test('API create-order rejects GET requests', async ({ page }) => {
        const response = await page.request.get('/src/Api/create-order.php');
        expect(response.status()).toBe(405);

        const data = await response.json();
        expect(data.success).toBe(false);
        expect(data.error).toContain('Method not allowed');
    });

    test('API create-order rejects invalid body', async ({ page }) => {
        const response = await page.request.post('/src/Api/create-order.php', {
            data: 'not-json',
            headers: { 'Content-Type': 'text/plain' },
        });
        expect(response.status()).toBe(400);
    });

    test('API create-order validates amount', async ({ page }) => {
        const response = await page.request.post('/src/Api/create-order.php', {
            data: { amount: -100, currency: 'EUR', description: 'test' },
        });
        expect(response.status()).toBe(400);

        const data = await response.json();
        expect(data.error).toContain('Invalid amount');
    });

    test('API create-order validates currency', async ({ page }) => {
        const response = await page.request.post('/src/Api/create-order.php', {
            data: { amount: 100, currency: 'XYZ', description: 'test' },
        });
        expect(response.status()).toBe(400);

        const data = await response.json();
        expect(data.error).toContain('Unsupported currency');
    });

    test('API get-order rejects invalid order ID', async ({ page }) => {
        const response = await page.request.get('/src/Api/get-order.php?id=<script>alert(1)</script>');
        expect(response.status()).toBe(400);

        const data = await response.json();
        expect(data.error).toContain('Invalid order ID');
    });

    test('footer displays correctly', async ({ page }) => {
        await page.goto('/');

        const footer = page.locator('footer');
        await expect(footer).toBeVisible();
        await expect(footer).toContainText('Revolut Virtual POS Demo');
        await expect(footer).toContainText('educational purposes');
    });

    test('page is responsive - mobile viewport', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/');

        // Products should still be visible
        const cards = page.locator('.product-card');
        await expect(cards).toHaveCount(3);

        // All cards should be visible (single column on mobile)
        for (let i = 0; i < 3; i++) {
            await expect(cards.nth(i)).toBeVisible();
        }
    });
});
