import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

test.describe('Profile', () => {
    test('edits the logged-in user profile', async ({ page }) => {
        const { email, password } = seedUser();

        await page.goto('/login');
        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page).toHaveURL('/dashboard');

        await page.goto('/profile');
        await expect(page.getByRole('heading', { name: 'My Profile' })).toBeVisible();
        await expect(page.getByLabel('Email')).toHaveValue(email);

        await page.getByLabel('Name').fill('Profile Edited');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('Profile updated successfully.')).toBeVisible();
        await expect(page.getByLabel('Name')).toHaveValue('Profile Edited');
    });

    test('requires the new email to be verified again', async ({ page }) => {
        const { email, password } = seedUser();

        await page.goto('/login');
        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page).toHaveURL('/dashboard');

        await page.goto('/profile');
        await expect(page.getByLabel('Email')).toHaveValue(email);

        await page.getByLabel('Email').fill(`changed-${Date.now()}@example.com`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/email\/verify$/);

        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/email\/verify$/);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/profile');

        await expect(page).toHaveURL(/\/login$/);
    });
});
