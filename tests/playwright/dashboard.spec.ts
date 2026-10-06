import { test, expect } from '@playwright/test';
import { resetDatabase, seedUser } from './support/seed';

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

    test('arranges account cards and keeps the order after reload', async ({ page }) => {
        resetDatabase();
        const { email, password } = seedUser();

        await page.goto('/login');
        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page).toHaveURL('/dashboard');

        for (const name of ['Card First', 'Card Second']) {
            await page.goto('/accounts/create');
            await page.getByLabel('Name', { exact: true }).fill(name);
            await page.getByLabel('Balance', { exact: true }).fill('1000');
            await page.getByRole('button', { name: 'Save' }).click();
            await expect(page).toHaveURL('/accounts');
        }

        await page.goto('/dashboard');
        await expect(page.locator('[data-account-card]').first()).toContainText('Card First');

        await page.getByRole('button', { name: 'Arrange order' }).click();
        const saved = page.waitForResponse((response) => response.url().includes('/accounts/order') && response.request().method() === 'PUT');
        await page.locator('[data-account-card]', { hasText: 'Card Second' }).getByRole('button', { name: 'Move left' }).click();
        expect((await saved).status()).toBe(204);
        await expect(page.locator('[data-account-card]').first()).toContainText('Card Second');

        await page.getByRole('button', { name: 'Done' }).click();
        await page.reload();
        await expect(page.locator('[data-account-card]').first()).toContainText('Card Second');
    });

    test('changes an account card color and icon from the dashboard modal', async ({ page }) => {
        const { email, password } = seedUser();
        const accountName = `Card ${Date.now()}`;

        await page.goto('/login');
        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page).toHaveURL('/dashboard');

        await page.goto('/accounts/create');
        await page.getByLabel('Name', { exact: true }).fill(accountName);
        await page.getByLabel('Balance', { exact: true }).fill('3000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/accounts');

        await page.goto('/dashboard');
        const card = page.locator('[data-account-card]', { hasText: accountName });
        await expect(card).toBeVisible();

        await card.getByRole('button', { name: 'Edit' }).click();
        const modal = page.locator('#account-appearance-modal');
        await expect(modal).toBeVisible();
        await modal.locator('input[name="color"]').fill('#2e7d32');
        await modal.locator('input[name="icon"][value="sprout"]').check({ force: true });
        await modal.getByRole('button', { name: 'Save' }).click();

        await expect(modal).toBeHidden();
        await expect(card).toHaveCSS('background-color', 'rgb(46, 125, 50)');
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page).toHaveURL(/\/login$/);
    });
});
