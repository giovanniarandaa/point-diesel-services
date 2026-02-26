import { test, expect } from '@playwright/test';

test.use({ storageState: { cookies: [], origins: [] } });

test('guest is redirected to login', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveURL(/\/login/);
});

test('user can login with valid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('test@example.com');
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: 'Log in' }).click();

    await expect(page).toHaveURL('/');
    await expect(page).toHaveTitle(/Dashboard/);
});

test('login fails with invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('wrong@example.com');
    await page.getByLabel('Password').fill('wrong-password');
    await page.getByRole('button', { name: 'Log in' }).click();

    await expect(page).toHaveURL(/\/login/);
    await expect(page.getByText('These credentials do not match our records.')).toBeVisible();
});
