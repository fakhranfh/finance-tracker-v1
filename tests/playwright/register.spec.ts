import { test, expect } from '@playwright/test';
import { resetDatabase } from './support/seed';

test.describe('Register', () => {
    test.beforeEach(() => resetDatabase());

    test('registers a new user with valid data (happy path)', async ({ page }) => {
        await page.goto('/register');

        const uniqueEmail = `e2e-${Date.now()}@example.com`;

        await page.getByLabel('Full name').fill('E2E Test User');
        await page.getByLabel('Email address').fill(uniqueEmail);
        await page.getByLabel('Password', { exact: true }).fill('Password123!');
        await page.getByLabel('Confirm password').fill('Password123!');

        await page.getByRole('button', { name: 'Create account' }).click();

        await expect(page).toHaveURL(/\/email\/verify$/);
    });

    test('shows a validation error when passwords do not match', async ({ page }) => {
        await page.goto('/register');

        await page.getByLabel('Full name').fill('E2E Test User');
        await page.getByLabel('Email address').fill(`e2e-${Date.now()}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('Password123!');
        await page.getByLabel('Confirm password').fill('Mismatch123!');

        await page.getByRole('button', { name: 'Create account' }).click();

        await expect(page.locator('[data-error-for="password"]')).toBeVisible();
        await expect(page).toHaveURL(/\/register$/);
    });
});
