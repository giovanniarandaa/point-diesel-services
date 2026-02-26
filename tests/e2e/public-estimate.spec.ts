import { test, expect } from '@playwright/test';

// Helper: create customer via the UI
async function createCustomer(page: import('@playwright/test').Page, name: string) {
    await page.goto('/customers/create');
    await page.getByLabel('Name').fill(name);
    await page.getByLabel('Phone').fill('+15550009999');
    await page.getByRole('button', { name: 'Create Customer' }).click();
    await expect(page).toHaveURL(/\/customers\/\d+/);
}

// Helper: create a part via the UI
async function createPart(page: import('@playwright/test').Page, sku: string, name: string) {
    await page.goto('/parts/create');
    await page.getByLabel('SKU').fill(sku);
    await page.locator('#name').fill(name);
    await page.getByLabel('Cost ($)').fill('10.00');
    await page.getByLabel('Sale Price ($)').fill('75.00');
    await page.locator('#stock').fill('50');
    await page.getByLabel('Minimum Stock').fill('5');
    await page.getByRole('button', { name: 'Create Part' }).click();
    await expect(page).toHaveURL(/\/parts\/\d+/);
}

// Helper: create estimate, send it, and return the public URL
async function createAndSendEstimate(page: import('@playwright/test').Page) {
    const customerName = 'PET Public Motors LLC';
    const partSku = 'PET-OIL-001';
    const partName = 'PET Oil Filter Premium';

    await createCustomer(page, customerName);
    await createPart(page, partSku, partName);

    await page.goto('/estimates/create');
    await page.locator('#customer_id').click();
    await page.getByRole('option', { name: customerName }).click();

    await page.getByRole('button', { name: 'Add Item' }).click();
    await page.getByPlaceholder('Search parts or services...').fill(partName);
    await page.getByText(partName).click();

    await page.getByRole('button', { name: 'Create Estimate' }).click();
    await expect(page).toHaveURL(/\/estimates\/\d+/);

    // Send the estimate
    page.on('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Send' }).click();
    await expect(page.getByText('Sent', { exact: true })).toBeVisible();

    // Grant clipboard permissions and copy the public link
    await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
    await page.getByRole('button', { name: 'Copy Link' }).click();
    const publicUrl = await page.evaluate(() => navigator.clipboard.readText());
    return publicUrl;
}

test.describe('Public Estimate Portal', () => {
    test.describe.configure({ mode: 'serial' });

    let publicUrl: string;

    test('can create and send an estimate', async ({ page }) => {
        publicUrl = await createAndSendEstimate(page);
        expect(publicUrl).toContain('/estimate/');
    });

    test('can view estimate via public link without authentication', async ({ browser }) => {
        const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const page = await context.newPage();

        await page.goto(publicUrl);

        // Verify estimate content is visible
        await expect(page.getByRole('heading', { name: /EST-\d+/ })).toBeVisible();
        await expect(page.getByText('PET Public Motors LLC')).toBeVisible();
        await expect(page.getByText('Line Items')).toBeVisible();
        await expect(page.getByText('PET Oil Filter Premium')).toBeVisible();
        await expect(page.getByText('Totals')).toBeVisible();

        // Verify action buttons
        await expect(page.getByRole('button', { name: 'Approve Estimate' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Call Shop' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'WhatsApp' })).toBeVisible();

        await context.close();
    });

    test('can approve estimate via public link', async ({ browser }) => {
        const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
        const page = await context.newPage();

        await page.goto(publicUrl);

        // Approve the estimate
        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Approve Estimate' }).click();

        // Wait for page to reload after redirect
        await expect(page).toHaveURL(publicUrl);

        // Verify approval banner is shown
        await expect(page.getByText('Already Approved')).toBeVisible();

        // Verify approve button is no longer visible
        await expect(page.getByRole('button', { name: 'Approve Estimate' })).not.toBeVisible();

        // Contact buttons should still be visible
        await expect(page.getByRole('link', { name: 'Call Shop' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'WhatsApp' })).toBeVisible();

        await context.close();
    });

    test('approved estimate shows correct status in admin view', async ({ page }) => {
        await page.goto('/estimates');

        // Filter by approved status
        await page.getByRole('button', { name: 'Approved' }).click();
        await expect(page).toHaveURL(/status=approved/);

        // Verify the estimate appears with approved status
        await expect(page.getByText('PET Public Motors LLC').first()).toBeVisible();
    });
});
