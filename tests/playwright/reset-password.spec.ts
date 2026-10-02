import { test, expect } from '@playwright/test';
import { seedResetToken } from './support/seed';

test.describe('Reset Password', () => {
    test('resets the password with a valid token (happy path)', async ({ page }) => {
        const { email, token } = seedResetToken();

        await page.goto(`/reset-password/${token}?email=${encodeURIComponent(email)}`);

        await page.getByLabel('New password', { exact: true }).fill('BrandNewPass123!');
        await page.getByLabel('Confirm new password').fill('BrandNewPass123!');

        await page.getByRole('button', { name: 'Reset password' }).click();

        await expect(page).toHaveURL(/\/login$/);
    });

    test('shows a validation error when the new password is too short', async ({ page }) => {
        const { email, token } = seedResetToken();

        await page.goto(`/reset-password/${token}?email=${encodeURIComponent(email)}`);

        await page.getByLabel('New password', { exact: true }).fill('a');
        await page.getByLabel('Confirm new password').fill('a');

        await page.getByRole('button', { name: 'Reset password' }).click();

        await expect(page.locator('[data-error-for="password"]')).toBeVisible();
    });
});
