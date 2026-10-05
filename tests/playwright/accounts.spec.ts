import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Accounts', () => {
    test('lists accounts and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.getByRole('link', { name: 'Manage Accounts' }).click();

        await expect(page).toHaveURL('/accounts');
        await expect(page.getByRole('heading', { name: 'Manage Accounts' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();

        await page.getByRole('link', { name: 'Add account' }).click();
        await expect(page).toHaveURL('/accounts/create');
    });

    test('creates, edits, and deletes a account end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/accounts/create');

        await page.getByLabel('Name', { exact: true }).fill(`${mainValue}`);
        await page.getByLabel('Balance', { exact: true }).fill('12500');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/accounts');
        await expect(page.getByText('Account created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/accounts');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this account?')).toBeVisible();
        await page.locator('#confirm-delete-account').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/accounts');

        await expect(page).toHaveURL(/\/login$/);
    });
});
