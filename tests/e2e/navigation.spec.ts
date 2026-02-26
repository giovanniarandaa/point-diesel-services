import { test, expect } from '@playwright/test';

test('can navigate to Customers from sidebar', async ({ page }) => {
    await page.goto('/');

    await page.getByRole('link', { name: 'Customers' }).click();

    await expect(page).toHaveURL('/customers');
    await expect(page.getByRole('heading', { name: 'Customers' })).toBeVisible();
});

test('can navigate back to Dashboard', async ({ page }) => {
    await page.goto('/customers');

    await page.getByRole('link', { name: 'Dashboard' }).click();

    await expect(page).toHaveURL('/');
});
