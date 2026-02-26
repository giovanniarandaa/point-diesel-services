import { test, expect } from '@playwright/test';

test.describe('Customers CRUD', () => {
    test('can view empty customers list', async ({ page }) => {
        await page.goto('/customers');

        await expect(page.getByRole('heading', { name: 'Customers' })).toBeVisible();
        await expect(page.getByText('No customers yet')).toBeVisible();
    });

    test('can navigate to create customer page', async ({ page }) => {
        await page.goto('/customers');
        await page.getByRole('link', { name: 'Add Customer' }).click();

        await expect(page.getByRole('heading', { name: 'Create Customer' })).toBeVisible();
    });

    test('can create a customer with phone and email', async ({ page }) => {
        await page.goto('/customers/create');

        await page.getByLabel('Name').fill('Acme Diesel Corp');
        await page.getByLabel('Phone').fill('+12025551234');
        await page.getByLabel('Email').fill('contact@acmediesel.com');
        await page.getByRole('button', { name: 'Create Customer' }).click();

        await expect(page).toHaveURL(/\/customers\/\d+/);
        await expect(page.getByRole('heading', { name: 'Acme Diesel Corp' })).toBeVisible();
        await expect(page.getByText('+12025551234')).toBeVisible();
        await expect(page.getByText('contact@acmediesel.com')).toBeVisible();
    });

    test('can create a customer with phone only', async ({ page }) => {
        await page.goto('/customers/create');

        await page.getByLabel('Name').fill('Phone Only LLC');
        await page.getByLabel('Phone').fill('+13015559999');
        await page.getByRole('button', { name: 'Create Customer' }).click();

        await expect(page).toHaveURL(/\/customers\/\d+/);
        await expect(page.getByRole('heading', { name: 'Phone Only LLC' })).toBeVisible();
    });

    test('shows validation error when name is empty', async ({ page }) => {
        await page.goto('/customers/create');

        await page.getByLabel('Phone').fill('+12025551234');
        await page.getByRole('button', { name: 'Create Customer' }).click();

        // HTML5 required validation prevents submission — page stays
        await expect(page).toHaveURL('/customers/create');
    });

    test('shows validation error for invalid phone format', async ({ page }) => {
        await page.goto('/customers/create');

        await page.getByLabel('Name').fill('Bad Phone Customer');
        await page.getByLabel('Phone').fill('1234567890');
        await page.getByRole('button', { name: 'Create Customer' }).click();

        await expect(page.getByText(/phone/i)).toBeVisible();
    });

    test('customer appears in the list after creation', async ({ page }) => {
        // Create a customer first
        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('Listed Customer Inc');
        await page.getByLabel('Phone').fill('+14155550001');
        await page.getByRole('button', { name: 'Create Customer' }).click();
        await expect(page).toHaveURL(/\/customers\/\d+/);

        // Go back to list
        await page.goto('/customers');
        await expect(page.getByText('Listed Customer Inc')).toBeVisible();
    });

    test('can search customers by name', async ({ page }) => {
        // Create two customers
        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('Alpha Trucking');
        await page.getByLabel('Phone').fill('+14155550002');
        await page.getByRole('button', { name: 'Create Customer' }).click();
        await expect(page).toHaveURL(/\/customers\/\d+/);

        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('Beta Diesel Services');
        await page.getByLabel('Phone').fill('+14155550003');
        await page.getByRole('button', { name: 'Create Customer' }).click();
        await expect(page).toHaveURL(/\/customers\/\d+/);

        // Search
        await page.goto('/customers');
        await page.getByPlaceholder('Search by name, phone, or email...').fill('Alpha');

        await expect(page.getByText('Alpha Trucking')).toBeVisible();
        await expect(page.getByText('Beta Diesel Services')).not.toBeVisible();
    });

    test('can view customer detail page', async ({ page }) => {
        // Create customer
        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('Detail View Corp');
        await page.getByLabel('Phone').fill('+14155550004');
        await page.getByLabel('Email').fill('info@detailview.com');
        await page.getByRole('button', { name: 'Create Customer' }).click();

        await expect(page.getByRole('heading', { name: 'Detail View Corp' })).toBeVisible();
        await expect(page.getByText('Contact Information')).toBeVisible();
        await expect(page.getByText('+14155550004')).toBeVisible();
        await expect(page.getByText('info@detailview.com')).toBeVisible();
        await expect(page.getByText('No units yet')).toBeVisible();
    });

    test('can edit a customer', async ({ page }) => {
        // Create customer
        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('Before Edit Corp');
        await page.getByLabel('Phone').fill('+14155550005');
        await page.getByRole('button', { name: 'Create Customer' }).click();
        await expect(page).toHaveURL(/\/customers\/\d+/);

        // Click edit
        await page.getByRole('link', { name: 'Edit', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Edit Customer' })).toBeVisible();

        // Update name
        await page.getByLabel('Name').clear();
        await page.getByLabel('Name').fill('After Edit Corp');
        await page.getByRole('button', { name: 'Save Changes' }).click();

        // Verify redirect to show page with updated name
        await expect(page.getByRole('heading', { name: 'After Edit Corp' })).toBeVisible();
    });

    test('can delete a customer', async ({ page }) => {
        // Create customer
        await page.goto('/customers/create');
        await page.getByLabel('Name').fill('To Delete Corp');
        await page.getByLabel('Phone').fill('+14155550006');
        await page.getByRole('button', { name: 'Create Customer' }).click();
        await expect(page).toHaveURL(/\/customers\/\d+/);

        // Accept the confirm dialog and delete
        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Delete' }).click();

        // Should redirect to customers list
        await expect(page).toHaveURL('/customers');
        await expect(page.getByText('To Delete Corp')).not.toBeVisible();
    });
});
