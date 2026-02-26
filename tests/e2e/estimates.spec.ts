import { test, expect } from '@playwright/test';

// Helper: create a customer via the UI
async function createCustomer(page: import('@playwright/test').Page, name: string) {
    await page.goto('/customers/create');
    await page.getByLabel('Name').fill(name);
    await page.getByLabel('Phone').fill('+15550001234');
    await page.getByRole('button', { name: 'Create Customer' }).click();
    await expect(page).toHaveURL(/\/customers\/\d+/);
}

// Helper: create a part via the UI
async function createPart(page: import('@playwright/test').Page, sku: string, name: string, price: string) {
    await page.goto('/parts/create');
    await page.getByLabel('SKU').fill(sku);
    await page.locator('#name').fill(name);
    await page.getByLabel('Cost ($)').fill('10.00');
    await page.getByLabel('Sale Price ($)').fill(price);
    await page.locator('#stock').fill('100');
    await page.getByLabel('Minimum Stock').fill('5');
    await page.getByRole('button', { name: 'Create Part' }).click();
    await expect(page).toHaveURL(/\/parts\/\d+/);
}

// Helper: create a service via the UI
async function createService(page: import('@playwright/test').Page, name: string, price: string) {
    await page.goto('/services/create');
    await page.getByLabel('Name').fill(name);
    await page.getByLabel('Default Price ($)').fill(price);
    await page.getByRole('button', { name: 'Create Service' }).click();
    await expect(page).toHaveURL(/\/services\/\d+/);
}

// Helper: create estimate prerequisites (customer + part) and create the estimate
async function createEstimateWith(page: import('@playwright/test').Page, customerName: string, partSku: string, partName: string) {
    await createCustomer(page, customerName);
    await createPart(page, partSku, partName, '50.00');

    await page.goto('/estimates/create');
    await page.locator('#customer_id').click();
    await page.getByRole('option', { name: customerName }).click();

    await page.getByRole('button', { name: 'Add Item' }).click();
    await page.getByPlaceholder('Search parts or services...').fill(partName);
    await page.getByText(partName).click();

    await page.getByRole('button', { name: 'Create Estimate' }).click();
    await expect(page).toHaveURL(/\/estimates\/\d+/);
}

test.describe('Estimates CRUD', () => {
    test.describe.configure({ mode: 'serial' });

    test('estimates index page loads', async ({ page }) => {
        await page.goto('/estimates');
        await expect(page.getByRole('heading', { name: 'Estimates' })).toBeVisible();
    });

    test('can navigate to create estimate page', async ({ page }) => {
        await page.goto('/estimates');
        await page.getByRole('link', { name: 'New Estimate' }).click();
        await expect(page.getByRole('heading', { name: 'New Estimate' })).toBeVisible();
    });

    test('can create an estimate with line items', async ({ page }) => {
        await createCustomer(page, 'EstTest Motors Inc');
        await createPart(page, 'ET-P001', 'ET Oil Filter HD', '25.00');
        await createService(page, 'ET Oil Change Full', '75.00');

        await page.goto('/estimates/create');

        // Select customer
        await page.locator('#customer_id').click();
        await page.getByRole('option', { name: 'EstTest Motors Inc' }).click();

        // Add part via catalog search
        await page.getByRole('button', { name: 'Add Item' }).click();
        await page.getByPlaceholder('Search parts or services...').fill('ET Oil Filter HD');
        await page.getByText('ET Oil Filter HD').click();

        // Add service via catalog search
        await page.getByRole('button', { name: 'Add Item' }).click();
        await page.getByPlaceholder('Search parts or services...').fill('ET Oil Change Full');
        await page.getByText('ET Oil Change Full').click();

        // Verify line items in table
        await expect(page.getByText('Part').first()).toBeVisible();
        await expect(page.getByText('Service').first()).toBeVisible();

        // Submit
        await page.getByRole('button', { name: 'Create Estimate' }).click();
        await expect(page).toHaveURL(/\/estimates\/\d+/);
        await expect(page.getByRole('heading', { name: /EST-\d+/ })).toBeVisible();
        await expect(page.getByText('EstTest Motors Inc')).toBeVisible();
    });

    test('estimate appears in the list after creation', async ({ page }) => {
        await page.goto('/estimates');
        await expect(page.getByText('EstTest Motors Inc')).toBeVisible();
    });

    test('can view estimate detail page with totals', async ({ page }) => {
        await page.goto('/estimates');
        // Click the estimate number link in the row containing EstTest Motors Inc
        const row = page.locator('tr', { hasText: 'EstTest Motors Inc' }).first();
        await row.getByRole('link').first().click();

        await expect(page).toHaveURL(/\/estimates\/\d+/);
        await expect(page.getByText('EstTest Motors Inc')).toBeVisible();
        await expect(page.getByText('Subtotal Parts')).toBeVisible();
        await expect(page.getByText('Subtotal Labor')).toBeVisible();
        await expect(page.getByText('Shop Supplies')).toBeVisible();
        await expect(page.getByText('Tax')).toBeVisible();
        await expect(page.getByText('Draft', { exact: true })).toBeVisible();
    });

    test('can edit a draft estimate', async ({ page }) => {
        await page.goto('/estimates');
        const row = page.locator('tr', { hasText: 'EstTest Motors Inc' }).first();
        await row.getByRole('link').first().click();
        await expect(page).toHaveURL(/\/estimates\/\d+/);
        await page.getByRole('link', { name: 'Edit' }).click();

        await expect(page.getByRole('heading', { name: 'Edit Estimate' })).toBeVisible();

        await page.locator('#notes').fill('Updated via E2E');
        await page.getByRole('button', { name: 'Update Estimate' }).click();

        await expect(page).toHaveURL(/\/estimates\/\d+/);
        await expect(page.getByText('Updated via E2E')).toBeVisible();
    });

    test('can send a draft estimate', async ({ page }) => {
        await page.goto('/estimates');
        const row = page.locator('tr', { hasText: 'EstTest Motors Inc' }).first();
        await row.getByRole('link').first().click();
        await expect(page).toHaveURL(/\/estimates\/\d+/);

        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Send' }).click();

        await expect(page.getByText('Sent', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Copy Link' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Send' })).not.toBeVisible();
    });

    test('can search estimates by customer name', async ({ page }) => {
        // Create another estimate with different customer
        await createEstimateWith(page, 'ET Beta Trucking LLC', 'ET-P002', 'ET Brake Pad HD');

        await page.goto('/estimates');
        await page.getByPlaceholder('Search by estimate # or customer...').fill('ET Beta');

        await expect(page.getByText('ET Beta Trucking LLC')).toBeVisible();
        await expect(page.getByText('EstTest Motors Inc')).not.toBeVisible();
    });

    test('can filter estimates by status', async ({ page }) => {
        await page.goto('/estimates');

        // Filter by Draft (Beta is draft, EstTest is sent)
        await page.getByRole('button', { name: 'Draft', exact: true }).click();
        await expect(page).toHaveURL(/status=draft/);
        await expect(page.getByText('ET Beta Trucking LLC')).toBeVisible();
    });

    test('can delete a draft estimate', async ({ page }) => {
        // Create a new estimate to delete
        await createEstimateWith(page, 'ET Delete Me Corp', 'ET-P003', 'ET Throwaway Part');

        // Should be on the show page after creation
        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Delete' }).click();

        await expect(page).toHaveURL('/estimates');
    });

    test('can navigate to Estimates from sidebar', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('link', { name: 'Estimates' }).click();

        await expect(page).toHaveURL('/estimates');
        await expect(page.getByRole('heading', { name: 'Estimates' })).toBeVisible();
    });
});
