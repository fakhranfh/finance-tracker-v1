import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Users', () => {
    test('lists users and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.getByRole('link', { name: 'Manage Users', exact: true }).click();

        await expect(page).toHaveURL('/users');
        await expect(page.getByRole('heading', { name: 'Manage Users' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();

        await page.getByRole('link', { name: 'Add user' }).click();
        await expect(page).toHaveURL('/users/create');
    });

    test('creates, edits, and deletes a user end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const newEmail = `e2e-${Date.now()}@example.com`;

        await login(page, email, password);
        await page.goto('/users/create');

        await page.getByLabel('Name').fill('Playwright User');
        await page.getByLabel('Email').fill(newEmail);
        await page.getByLabel('Password', { exact: true }).fill('Passw0rd!123');
        await page.getByLabel('Confirm password').fill('Passw0rd!123');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/users');
        await expect(page.getByText('User created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(newEmail) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name')).toHaveValue('Playwright User');
        await page.getByLabel('Name').fill('Playwright User Edited');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/users');
        const editedRow = page.getByRole('row', { name: new RegExp(newEmail) });
        await expect(editedRow.getByText('Playwright User Edited')).toBeVisible();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this user?')).toBeVisible();
        await page.locator('#confirm-delete-user').click();

        await expect(page.getByRole('row', { name: new RegExp(newEmail) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/users');

        await expect(page).toHaveURL(/\/login$/);
    });
});
