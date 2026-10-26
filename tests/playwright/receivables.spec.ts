import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Receivables', () => {
    test('opens receivables from the shared sidebar item and keeps them apart from debts', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Debts & Receivables' }).click();
        await page.getByRole('main').getByRole('link', { name: 'Manage Receivables' }).click();

        await expect(page).toHaveURL('/receivables');
        await expect(page.getByRole('heading', { name: 'Manage Receivables' })).toBeVisible();

        await page.getByRole('link', { name: 'Add receivable' }).click();
        await expect(page).toHaveURL('/receivables/create');

        await page.getByLabel('Name', { exact: true }).fill(mainValue);
        await page.getByLabel('Amount', { exact: true }).fill('50000');
        await page.getByLabel('Paid Amount', { exact: true }).fill('0');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/receivables');
        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toBeVisible();

        await page.goto('/debts');
        await expect(page.getByRole('table')).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/receivables');
        await expect(page).toHaveURL(/\/login/);
    });
});
