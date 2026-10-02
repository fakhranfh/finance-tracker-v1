import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

test.describe('Login', () => {
    test('logs in with valid credentials (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await page.goto('/login');

        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);

        await page.getByRole('button', { name: 'Log in' }).click();

        await expect(page).toHaveURL('/dashboard');
    });

    test('shows an error for invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.getByLabel('Email address').fill('nobody@example.com');
        await page.getByLabel('Password', { exact: true }).fill('wrong-password');

        await page.getByRole('button', { name: 'Log in' }).click();

        await expect(page.locator('[data-error-for="email"]')).toBeVisible();
        await expect(page).toHaveURL(/\/login$/);
    });
});
