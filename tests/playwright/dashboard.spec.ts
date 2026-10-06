import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

test.describe('Dashboard', () => {
    test('shows the dashboard with sidebar and topbar after login (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await page.goto('/login');
        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in' }).click();

        await expect(page).toHaveURL('/dashboard');
        await expect(page.getByRole('link', { name: 'Add account' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Log out' })).toBeVisible();
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page).toHaveURL(/\/login$/);
    });
});
