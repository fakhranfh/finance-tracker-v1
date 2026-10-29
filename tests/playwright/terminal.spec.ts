import { test, expect } from '@playwright/test';
import { seedAdmin, seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Terminal', () => {
    test('sets a password, locks, unlocks, runs a command and changes the password', async ({ page }) => {
        const { email, password } = seedAdmin();

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Terminal' }).click();

        await expect(page).toHaveURL('/terminal');
        await expect(page.getByRole('heading', { name: 'Terminal', exact: true })).toBeVisible();

        // The e2e database is shared across runs, so the password may already be set.
        const setupHeading = page.getByRole('heading', { name: 'Set a terminal password' });
        const unlockField = page.getByLabel('Terminal password');
        await expect(page.locator('#terminal-skeleton')).toBeHidden();

        if (await setupHeading.isVisible()) {
            await page.getByLabel('New password', { exact: true }).fill('Terminal123!');
            await page.getByLabel('Confirm new password').fill('Terminal123!');
            await page.getByRole('button', { name: 'Save password' }).click();
            await expect(page.getByText('Terminal password saved.')).toBeVisible();
            await page.getByRole('button', { name: 'Lock' }).click();
        }

        await page.getByRole('button', { name: 'Forgot password?' }).click();
        await expect(page.getByText(/A reset link was sent to .+\. It is valid for 60 minutes\./)).toBeVisible();

        await unlockField.fill('wrong-password');
        await page.getByRole('button', { name: 'Unlock' }).click();
        await expect(page.getByText('Wrong terminal password.')).toBeVisible();

        await unlockField.fill('Terminal123!');
        await page.getByRole('button', { name: 'Unlock' }).click();

        await page.getByLabel('Command', { exact: true }).fill('php artisan list');
        await page.getByRole('button', { name: 'Run' }).click();
        await expect(page.locator('[data-terminal-output]')).toContainText('migrate');

        await page.getByLabel('Command', { exact: true }).fill('definitely-not-a-command');
        await page.getByRole('button', { name: 'Run' }).click();
        await expect(page.getByText('exit code')).toBeVisible();

        // Change the password to the same value so reruns keep working.
        await page.getByLabel('Current password').fill('wrong-password');
        await page.getByLabel('New password', { exact: true }).fill('Terminal123!');
        await page.getByLabel('Confirm new password').fill('Terminal123!');
        await page.getByRole('button', { name: 'Save password' }).click();
        await expect(page.getByText('Wrong terminal password.').last()).toBeVisible();

        await page.getByLabel('Current password').fill('Terminal123!');
        await page.getByRole('button', { name: 'Save password' }).click();
        await expect(page.getByText('Terminal password saved.')).toBeVisible();

        await page.getByRole('button', { name: 'Lock' }).click();
        await expect(unlockField).toBeVisible();
    });

    test('forbids a regular user', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        const response = await page.goto('/terminal');

        expect(response?.status()).toBe(403);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/terminal');

        await expect(page).toHaveURL(/\/login$/);
    });
});
