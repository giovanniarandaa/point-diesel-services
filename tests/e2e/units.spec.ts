import { test, expect } from '@playwright/test';

test.describe('Units CRUD', () => {
    let customerUrl: string;

    test.beforeEach(async ({ page }) => {
        // Create a customer to work with
        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('Unit Test Customer');
        await page.getByLabel('Phone').fill('+14155550100');
        await page.getByRole('button', { name: 'Create Customer' }).click();
        await expect(page).toHaveURL(/\/customers\/\d+/);
        customerUrl = page.url();
    });

    test('can add a unit to a customer', async ({ page }) => {
        await page.getByRole('button', { name: 'Add Unit' }).click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByText('Add a new vehicle')).toBeVisible();

        await page.getByLabel('VIN').fill('ABCDEFGH123456789');
        await page.getByLabel('Make').fill('Freightliner');
        await page.getByLabel('Model').fill('Cascadia');
        await page.getByLabel('Engine').fill('DD15');
        await page.getByLabel('Mileage').fill('50000');
        await page.getByRole('button', { name: 'Add Unit' }).click();

        // Dialog closes, unit card appears
        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(page.getByText('Freightliner Cascadia')).toBeVisible();
        await expect(page.getByText('ABCDEFGH123456789')).toBeVisible();
        await expect(page.getByText('DD15')).toBeVisible();
        await expect(page.getByText('50,000 mi')).toBeVisible();
    });

    test('VIN is uppercased automatically', async ({ page }) => {
        await page.getByRole('button', { name: 'Add Unit' }).click();

        await page.getByLabel('VIN').fill('abcdefgh123456789');

        // Input should show uppercase
        await expect(page.getByLabel('VIN')).toHaveValue('ABCDEFGH123456789');
    });

    test('shows VIN character counter', async ({ page }) => {
        await page.getByRole('button', { name: 'Add Unit' }).click();

        await expect(page.getByText('0/17')).toBeVisible();

        await page.getByLabel('VIN').fill('ABCDEFGH123456789');

        await expect(page.getByText('17/17')).toBeVisible();
    });

    test('can edit a unit', async ({ page }) => {
        // Create a unit first
        await page.getByRole('button', { name: 'Add Unit' }).click();
        await page.getByLabel('VIN').fill('EDTTSTGH123456789');
        await page.getByLabel('Make').fill('Peterbilt');
        await page.getByLabel('Model').fill('579');
        await page.getByLabel('Mileage').fill('30000');

        await page.getByRole('dialog').getByRole('button', { name: 'Add Unit' }).click();

        // Wait for page to reload with the new unit
        await expect(page.getByText('Peterbilt 579')).toBeVisible({ timeout: 10000 });

        // Click the edit button on the unit card
        const unitCard = page.locator('[class*="card"]').filter({ hasText: 'Peterbilt 579' });
        await unitCard.getByRole('button').first().click();

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(page.getByText('Edit Unit')).toBeVisible();

        // Update mileage
        await page.getByLabel('Mileage').clear();
        await page.getByLabel('Mileage').fill('35000');
        await page.getByRole('dialog').getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.getByText('35,000 mi')).toBeVisible({ timeout: 10000 });
    });

    test('can delete a unit', async ({ page }) => {
        // Create a unit first
        await page.getByRole('button', { name: 'Add Unit' }).click();
        await page.getByLabel('VIN').fill('XYZXYZXY123456789');
        await page.getByLabel('Make').fill('Kenworth');
        await page.getByLabel('Model').fill('T680');
        await page.getByLabel('Mileage').fill('60000');
        await page.getByRole('button', { name: 'Add Unit' }).click();
        await expect(page.getByText('Kenworth T680')).toBeVisible();

        // Accept the confirm dialog and click delete (second ghost button)
        page.on('dialog', (dialog) => dialog.accept());
        const unitCard = page.locator('text=Kenworth T680').locator('..').locator('..');
        await unitCard.getByRole('button').nth(1).click();

        // Unit should disappear
        await expect(page.getByText('Kenworth T680')).not.toBeVisible();
        await expect(page.getByText('No units yet')).toBeVisible();
    });

    test('shows empty state when no units', async ({ page }) => {
        await expect(page.getByText('No units yet')).toBeVisible();
    });
});
