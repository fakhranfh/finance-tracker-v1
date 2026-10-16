import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Webhooks', () => {
    test('lists webhooks and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Manage Webhooks' }).click();

        await expect(page).toHaveURL('/webhooks');
        await expect(page.getByRole('heading', { name: 'Manage Webhooks' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();
        await page.getByText('How to use webhooks').click();
        await expect(page.getByText('Example request')).toBeVisible();

        await page.getByRole('link', { name: 'Add webhook' }).click();
        await expect(page).toHaveURL('/webhooks/create');
    });

    test('creates, edits, and deletes a webhook end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/webhooks/create');

        await page.getByLabel('Name', { exact: true }).fill(`${mainValue}`);
        await page.getByLabel('Allowed IP addresses').fill('203.0.113.5');
        await page.getByLabel('Is Active', { exact: true }).check();
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/webhooks');
        await expect(page.getByText('Webhook created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
        const token = await page.getByLabel('Token', { exact: true }).inputValue();
        expect(token).toHaveLength(40);
        await page.locator('[data-copy-target="token"]').click();
        await expect(page.locator('[data-copy-target="token"]')).toHaveText('Copied!');
        expect(await page.evaluate(() => navigator.clipboard.readText())).toBe(token);
        await page.getByRole('link', { name: 'Webhook logs' }).click();
        await expect(page).toHaveURL(/\/webhooks\/\d+\/logs$/);
        await expect(page.getByRole('heading', { name: 'Webhook logs' })).toBeVisible();
        await page.getByRole('link', { name: 'Back to edit webhook' }).click();
        await page.getByRole('link', { name: 'Test webhook' }).click();
        await expect(page).toHaveURL(/\/webhooks\/\d+\/test$/);
        await expect(page.locator('#webhook-test-payload')).toBeVisible();
        await page.getByRole('button', { name: 'Send test' }).click();
        await expect(page.locator('#webhook-test-result')).toBeVisible();
        await page.getByRole('link', { name: 'Back to edit webhook' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/webhooks');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        const toggle = editedRow.locator('[data-webhook-active]');
        await expect(toggle).toBeChecked();
        await toggle.uncheck();
        await page.reload();
        await expect(page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) }).locator('[data-webhook-active]')).not.toBeChecked();
        await editedRow.getByRole('link', { name: 'Test' }).click();
        await expect(page).toHaveURL(/\/webhooks\/\d+\/test$/);
        await page.goBack();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this webhook?')).toBeVisible();
        await page.locator('#confirm-delete-webhook').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/webhooks');

        await expect(page).toHaveURL(/\/login$/);
    });
});
