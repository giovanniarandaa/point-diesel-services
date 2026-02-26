import { test, expect } from '@playwright/test';

test.describe('Parts (Inventory) CRUD', () => {
    test('can view empty parts list', async ({ page }) => {
        await page.goto('/parts');

        await expect(page.getByRole('heading', { name: 'Inventory' })).toBeVisible();
        await expect(page.getByText('No parts yet')).toBeVisible();
    });

    test('can navigate to create part page', async ({ page }) => {
        await page.goto('/parts');
        await page.getByRole('link', { name: 'Add Part' }).click();

        await expect(page.getByRole('heading', { name: 'Add Part' })).toBeVisible();
    });

    test('can create a part', async ({ page }) => {
        await page.goto('/parts/create');

        await page.getByLabel('SKU').fill('FLT-0001');
        await page.locator('#name').fill('Oil Filter');
        await page.getByLabel('Description').fill('Heavy duty oil filter');
        await page.getByLabel('Cost ($)').fill('15.50');
        await page.getByLabel('Sale Price ($)').fill('25.00');
        await page.locator('#stock').fill('50');
        await page.getByLabel('Minimum Stock').fill('5');
        await page.getByRole('button', { name: 'Create Part' }).click();

        await expect(page).toHaveURL(/\/parts\/\d+/);
        await expect(page.getByRole('heading', { name: 'Oil Filter' })).toBeVisible();
        await expect(page.getByText('FLT-0001')).toBeVisible();
    });

    test('SKU is uppercased automatically', async ({ page }) => {
        await page.goto('/parts/create');

        await page.getByLabel('SKU').fill('flt-0002');

        await expect(page.getByLabel('SKU')).toHaveValue('FLT-0002');
    });

    test('shows validation error for duplicate SKU', async ({ page }) => {
        // Create first part
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('DUP-0001');
        await page.locator('#name').fill('First Part');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('10');
        await page.getByLabel('Minimum Stock').fill('2');
        await page.getByRole('button', { name: 'Create Part' }).click();
        await expect(page).toHaveURL(/\/parts\/\d+/);

        // Try to create with same SKU
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('DUP-0001');
        await page.locator('#name').fill('Duplicate Part');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('10');
        await page.getByLabel('Minimum Stock').fill('2');
        await page.getByRole('button', { name: 'Create Part' }).click();

        await expect(page.getByText(/sku/i)).toBeVisible();
    });

    test('part appears in the list after creation', async ({ page }) => {
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('LST-0001');
        await page.locator('#name').fill('Listed Part');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('10');
        await page.getByLabel('Minimum Stock').fill('2');
        await page.getByRole('button', { name: 'Create Part' }).click();
        await expect(page).toHaveURL(/\/parts\/\d+/);

        await page.goto('/parts');
        await expect(page.getByText('Listed Part')).toBeVisible();
    });

    test('can search parts by name', async ({ page }) => {
        // Create two parts
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('SRC-0001');
        await page.locator('#name').fill('Alpha Oil Filter');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('10');
        await page.getByLabel('Minimum Stock').fill('2');
        await page.getByRole('button', { name: 'Create Part' }).click();
        await expect(page).toHaveURL(/\/parts\/\d+/);

        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('SRC-0002');
        await page.locator('#name').fill('Beta Brake Pad');
        await page.getByLabel('Cost ($)').fill('30.00');
        await page.getByLabel('Sale Price ($)').fill('50.00');
        await page.locator('#stock').fill('20');
        await page.getByLabel('Minimum Stock').fill('3');
        await page.getByRole('button', { name: 'Create Part' }).click();
        await expect(page).toHaveURL(/\/parts\/\d+/);

        // Search
        await page.goto('/parts');
        await page.getByPlaceholder('Search by name or SKU...').fill('Alpha');

        await expect(page.getByText('Alpha Oil Filter')).toBeVisible();
        await expect(page.getByText('Beta Brake Pad')).not.toBeVisible();
    });

    test('can view part detail page', async ({ page }) => {
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('DTL-0001');
        await page.locator('#name').fill('Detail View Part');
        await page.getByLabel('Description').fill('Part for detail testing');
        await page.getByLabel('Cost ($)').fill('15.00');
        await page.getByLabel('Sale Price ($)').fill('30.00');
        await page.locator('#stock').fill('25');
        await page.getByLabel('Minimum Stock').fill('5');
        await page.getByRole('button', { name: 'Create Part' }).click();

        await expect(page.getByRole('heading', { name: 'Detail View Part' })).toBeVisible();
        await expect(page.getByText('DTL-0001')).toBeVisible();
        await expect(page.getByText('$15.00').first()).toBeVisible();
        await expect(page.getByText('$30.00').first()).toBeVisible();
    });

    test('shows low stock badge when stock is low', async ({ page }) => {
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('LOW-0001');
        await page.locator('#name').fill('Low Stock Part');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('2');
        await page.getByLabel('Minimum Stock').fill('5');
        await page.getByRole('button', { name: 'Create Part' }).click();

        await expect(page.getByText('Low Stock', { exact: true })).toBeVisible();
    });

    test('can edit a part', async ({ page }) => {
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('EDT-0001');
        await page.locator('#name').fill('Before Edit Part');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('10');
        await page.getByLabel('Minimum Stock').fill('2');
        await page.getByRole('button', { name: 'Create Part' }).click();
        await expect(page).toHaveURL(/\/parts\/\d+/);

        await page.getByRole('link', { name: 'Edit', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Edit Part' })).toBeVisible();

        await page.locator('#name').clear();
        await page.locator('#name').fill('After Edit Part');
        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.getByRole('heading', { name: 'After Edit Part' })).toBeVisible();
    });

    test('can delete a part', async ({ page }) => {
        await page.goto('/parts/create');
        await page.getByLabel('SKU').fill('DEL-0001');
        await page.locator('#name').fill('To Delete Part');
        await page.getByLabel('Cost ($)').fill('10.00');
        await page.getByLabel('Sale Price ($)').fill('20.00');
        await page.locator('#stock').fill('10');
        await page.getByLabel('Minimum Stock').fill('2');
        await page.getByRole('button', { name: 'Create Part' }).click();
        await expect(page).toHaveURL(/\/parts\/\d+/);

        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Delete' }).click();

        await expect(page).toHaveURL('/parts');
        await expect(page.getByText('To Delete Part')).not.toBeVisible();
    });
});
