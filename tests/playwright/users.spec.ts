import { test, expect } from '@playwright/test';
import { seedAdmin, seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Users', () => {
    test('lists users and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedAdmin();

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Manage Users' }).click();

        await expect(page).toHaveURL('/users');
        await expect(page.getByRole('heading', { name: 'Manage Users' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(email) })).toContainText('Admin');

        await page.getByRole('link', { name: 'Add user' }).click();
        await expect(page).toHaveURL('/users/create');
    });

    test('creates, edits, and deletes a user end-to-end', async ({ page }) => {
        const { email, password } = seedAdmin();
        const mainValue = `E2E ${Date.now()}`;
        const newEmail = `e2e-${Date.now()}@example.com`;

        await login(page, email, password);
        await page.goto('/users/create');

        await page.getByLabel('Name', { exact: true }).fill(mainValue);
        await page.getByLabel('Email', { exact: true }).fill(newEmail);
        await page.getByRole('radio', { name: 'User', exact: true }).check();
        await page.getByLabel('Password', { exact: true }).fill('Password123!');
        await page.getByLabel('Confirm password', { exact: true }).fill('Password123!');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/users');
        await expect(page.getByText('User created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await expect(page.getByRole('radio', { name: 'User', exact: true })).toBeChecked();
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('radio', { name: 'Admin', exact: true }).check();
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/users');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await expect(editedRow).toContainText('Admin');
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this user?')).toBeVisible();
        await page.locator('#confirm-delete-user').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('lets a created user log in and keeps them out of user management', async ({ page }) => {
        const admin = seedAdmin();
        const newEmail = `e2e-${Date.now()}@example.com`;

        await login(page, admin.email, admin.password);
        await page.goto('/users/create');
        await page.getByLabel('Name', { exact: true }).fill('Regular Person');
        await page.getByLabel('Email', { exact: true }).fill(newEmail);
        await page.getByLabel('Password', { exact: true }).fill('Password123!');
        await page.getByLabel('Confirm password', { exact: true }).fill('Password123!');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/users');

        await page.context().clearCookies();
        await login(page, newEmail, 'Password123!');

        await expect(page.locator('aside').getByRole('link', { name: 'Manage Users' })).toHaveCount(0);
        const response = await page.goto('/users');
        expect(response?.status()).toBe(403);
    });

    test('hides user management from a regular user', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);

        await expect(page.locator('aside').getByRole('link', { name: 'Manage Users' })).toHaveCount(0);
        const response = await page.goto('/users');
        expect(response?.status()).toBe(403);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/users');

        await expect(page).toHaveURL(/\/login$/);
    });
});
