import { test, expect } from '@playwright/test';

test.describe('Labor Services CRUD', () => {
    test('services index page loads', async ({ page }) => {
        await page.goto('/services');

        await expect(page.getByRole('heading', { name: 'Services' })).toBeVisible();
    });

    test('can navigate to create service page', async ({ page }) => {
        await page.goto('/services');
        await page.getByRole('link', { name: 'Add Service' }).click();

        await expect(page).toHaveURL('/services/create');
        await expect(page.getByRole('heading', { name: 'Add Service' })).toBeVisible();
    });

    test('can create a service', async ({ page }) => {
        await page.goto('/services/create');

        await page.getByLabel('Name').fill('Oil Change');
        await page.getByLabel('Description').fill('Complete oil and filter change');
        await page.getByLabel('Default Price ($)').fill('75.00');
        await page.getByRole('button', { name: 'Create Service' }).click();

        await expect(page).toHaveURL(/\/services\/\d+/);
        await expect(page.getByRole('heading', { name: 'Oil Change' })).toBeVisible();
        await expect(page.getByText('$75.00')).toBeVisible();
    });

    test('service appears in the list after creation', async ({ page }) => {
        await page.goto('/services/create');
        await page.getByLabel('Name').fill('Listed Service');
        await page.getByLabel('Default Price ($)').fill('100.00');
        await page.getByRole('button', { name: 'Create Service' }).click();
        await expect(page).toHaveURL(/\/services\/\d+/);

        await page.goto('/services');
        await expect(page.getByText('Listed Service')).toBeVisible();
    });

    test('can search services by name', async ({ page }) => {
        // Create two services
        await page.goto('/services/create');
        await page.getByLabel('Name').fill('Alpha Brake Service');
        await page.getByLabel('Default Price ($)').fill('120.00');
        await page.getByRole('button', { name: 'Create Service' }).click();
        await expect(page).toHaveURL(/\/services\/\d+/);

        await page.goto('/services/create');
        await page.getByLabel('Name').fill('Beta Engine Diagnostic');
        await page.getByLabel('Default Price ($)').fill('80.00');
        await page.getByRole('button', { name: 'Create Service' }).click();
        await expect(page).toHaveURL(/\/services\/\d+/);

        // Search
        await page.goto('/services');
        await page.getByPlaceholder('Search by name...').fill('Alpha');

        await expect(page.getByText('Alpha Brake Service')).toBeVisible();
        await expect(page.getByText('Beta Engine Diagnostic')).not.toBeVisible();
    });

    test('can view service detail page', async ({ page }) => {
        await page.goto('/services/create');
        await page.getByLabel('Name').fill('Detail View Service');
        await page.getByLabel('Description').fill('Service for detail testing');
        await page.getByLabel('Default Price ($)').fill('150.00');
        await page.getByRole('button', { name: 'Create Service' }).click();

        await expect(page.getByRole('heading', { name: 'Detail View Service' })).toBeVisible();
        await expect(page.getByText('$150.00')).toBeVisible();
        await expect(page.getByText('Service for detail testing')).toBeVisible();
    });

    test('can edit a service', async ({ page }) => {
        await page.goto('/services/create');
        await page.getByLabel('Name').fill('Before Edit Service');
        await page.getByLabel('Default Price ($)').fill('50.00');
        await page.getByRole('button', { name: 'Create Service' }).click();
        await expect(page).toHaveURL(/\/services\/\d+/);

        await page.getByRole('link', { name: 'Edit', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Edit Service' })).toBeVisible();

        await page.getByLabel('Name').clear();
        await page.getByLabel('Name').fill('After Edit Service');
        await page.getByRole('button', { name: 'Save Changes' }).click();

        await expect(page.getByRole('heading', { name: 'After Edit Service' })).toBeVisible();
    });

    test('can delete a service', async ({ page }) => {
        await page.goto('/services/create');
        await page.getByLabel('Name').fill('To Delete Service');
        await page.getByLabel('Default Price ($)').fill('50.00');
        await page.getByRole('button', { name: 'Create Service' }).click();
        await expect(page).toHaveURL(/\/services\/\d+/);

        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Delete' }).click();

        await expect(page).toHaveURL('/services');
        await expect(page.getByText('To Delete Service')).not.toBeVisible();
    });
});
