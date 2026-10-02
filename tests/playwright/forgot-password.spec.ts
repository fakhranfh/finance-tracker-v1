import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

test.describe('Forgot Password', () => {
    test('sends a reset link for an existing account (happy path)', async ({ page }) => {
        const { email } = seedUser();

        await page.goto('/forgot-password');

        await page.getByLabel('Email address').fill(email);
        await page.getByRole('button', { name: 'Send reset link' }).click();

        await expect(page.locator('[data-form-success]')).toBeVisible({ timeout: 15_000 });
    });

    test('shows a validation error for an empty email', async ({ page }) => {
        await page.goto('/forgot-password');

        await page.getByRole('button', { name: 'Send reset link' }).click();

        await expect(page.locator('[data-error-for="email"]')).toBeVisible();
    });
});
