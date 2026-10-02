import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

test.describe('Logout', () => {
    test('logs the authenticated user out (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await page.goto('/login');
        await page.getByLabel('Email address').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(password);
        await page.getByRole('button', { name: 'Log in' }).click();
        await expect(page).toHaveURL('/dashboard');

        // The app has no dedicated logout page/button in the header per the
        // current design, so exercise the API-backed logout action directly,
        // then confirm the session no longer grants access to a guest-only page.
        const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const logoutResponse = await page.request.post('/api/logout', {
            headers: { 'X-CSRF-TOKEN': csrfToken ?? '' },
        });
        expect(logoutResponse.ok()).toBeTruthy();

        await page.goto('/login');
        await expect(page).toHaveURL(/\/login$/);
    });
});
