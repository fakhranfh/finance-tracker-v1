import { test, expect } from '@playwright/test';
import { resetDatabase, seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Records', () => {
    test('prompts to create an account when none exists', async ({ page }) => {
        resetDatabase();
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.getByRole('main').getByRole('link', { name: 'Manage Records' }).click();

        await expect(page).toHaveURL('/records');
        await expect(page.getByText('You need at least one account before adding a record.')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Add record' })).toHaveCount(0);

        await page.getByRole('link', { name: 'Add account' }).click();
        await expect(page).toHaveURL('/accounts/create');
    });

    test('lists records and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);

        await page.goto('/accounts/create');
        await page.getByLabel('Name', { exact: true }).fill('E2E Cash');
        await page.getByLabel('Balance', { exact: true }).fill('100000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/accounts');

        await page.getByRole('link', { name: 'Manage Records' }).click();

        await expect(page).toHaveURL('/records');
        await expect(page.getByRole('heading', { name: 'Manage Records' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();
        await expect(page.getByText('Total income')).toBeVisible();
        await expect(page.getByText('Total expense')).toBeVisible();

        await page.getByLabel('Period').selectOption('this_year');
        await expect(page.getByLabel('From date')).toHaveValue(`${new Date().getFullYear()}-01-01`);
        await expect(page.getByLabel('To date')).toHaveValue(`${new Date().getFullYear()}-12-31`);
        await page.getByLabel('From date').fill(`${new Date().getFullYear()}-02-01`);
        await page.getByLabel('From date').dispatchEvent('change');
        await expect(page.getByLabel('Period')).toHaveValue('');

        await page.getByRole('link', { name: 'Add record' }).click();
        await expect(page).toHaveURL('/records/create');
    });

    test('creates, edits, and deletes a record end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/accounts/create');
        await page.getByLabel('Name', { exact: true }).fill('E2E Cash');
        await page.getByLabel('Balance', { exact: true }).fill('100000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/accounts');

        await page.goto('/records/create');

        await page.getByText('Income', { exact: true }).click();
        await page.getByLabel('Account', { exact: true }).selectOption({ label: 'E2E Cash' });
        await page.getByLabel('Amount', { exact: true }).fill('12500');
        await page.getByLabel('Note', { exact: true }).fill(mainValue);
        await page.getByLabel('Date', { exact: true }).fill('2026-01-15T10:30');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/records');
        await expect(page.getByText('Record created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Note', { exact: true })).toHaveValue(mainValue);
        await page.getByLabel('Note', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/records');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this record?')).toBeVisible();
        await page.locator('#confirm-delete-record').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/records');

        await expect(page).toHaveURL(/\/login$/);
    });
});
