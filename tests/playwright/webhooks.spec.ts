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
        await expect(page.getByLabel('Allow all')).toBeChecked();
        await page.getByLabel('Allow only').check();
        await expect(page.locator('[data-ip-input]')).toHaveCount(1);
        await page.getByRole('button', { name: 'Add IP address' }).click();
        await expect(page.locator('[data-ip-input]')).toHaveCount(2);
        await page.locator('[data-ip-input]').nth(0).fill('203.0.113.5');
        await page.locator('[data-ip-delete]').nth(1).click();
        await expect(page.locator('[data-ip-input]')).toHaveCount(1);
        await page.getByRole('button', { name: 'Delete all' }).click();
        await expect(page.locator('[data-ip-input]')).toHaveCount(0);
        await page.getByRole('button', { name: 'Add IP address' }).click();
        await page.locator('[data-ip-input]').nth(0).fill('203.0.113.5');
        for (const type of ['Income', 'Expense', 'Transfer']) {
            await expect(page.getByLabel(type, { exact: true })).toBeChecked();
        }
        await page.locator('[data-toggle-all="allowed_types"]').click();
        await expect(page.getByLabel('Expense', { exact: true })).not.toBeChecked();
        await page.locator('[data-toggle-all="allowed_types"]').click();
        await expect(page.getByLabel('Transfer', { exact: true })).toBeChecked();
        await page.getByLabel('Income', { exact: true }).uncheck();
        await page.getByLabel('Transfer', { exact: true }).uncheck();
        await expect(page.getByLabel('Allow creating accounts')).not.toBeChecked();
        await page.getByLabel('Allow creating accounts').check();
        await page.getByLabel('Allow creating categories').check();
        await page.getByLabel('Is Active', { exact: true }).check();
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/webhooks');
        await expect(page.getByText('Webhook created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'View' }).click();
        await expect(page).toHaveURL(/\/webhooks\/\d+$/);
        await expect(page.getByRole('heading', { name: mainValue })).toBeVisible();
        await expect(page.locator('[data-field="allowed_ips"]')).toHaveText('203.0.113.5');
        await expect(page.locator('[data-field="allowed_types"]')).toHaveText('Expense');
        await expect(page.locator('[data-field="allowed_account_ids"]')).toHaveText('All');
        await expect(page.locator('[data-field="can_create_accounts"]')).toHaveText('Yes');
        await expect(page.locator('[data-field="can_create_categories"]')).toHaveText('Yes');
        await page.getByRole('link', { name: 'Webhook logs' }).click();
        await page.getByRole('link', { name: 'Back to webhook details' }).click();
        await expect(page).toHaveURL(/\/webhooks\/\d+$/);
        await page.getByRole('link', { name: 'Test webhook' }).click();
        await expect(page).toHaveURL(/\/webhooks\/api-docs\?webhook=\d+$/);
        await expect(page.getByRole('heading', { name: 'Webhook API' })).toBeVisible();
        await page.goBack();
        await expect(page).toHaveURL(/\/webhooks\/\d+$/);
        await page.getByRole('link', { name: 'Edit', exact: true }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
        const token = await page.getByLabel('Token', { exact: true }).inputValue();
        expect(token).toHaveLength(40);
        await page.locator('[data-copy-target="token"]').click();
        await expect(page.locator('[data-copy-target="token"]')).toHaveText('Copied!');
        expect(await page.evaluate(() => navigator.clipboard.readText())).toBe(token);
        await expect(page.getByLabel('Allow only')).toBeChecked();
        await expect(page.locator('[data-ip-input]').nth(0)).toHaveValue('203.0.113.5');
        await expect(page.getByLabel('Expense', { exact: true })).toBeChecked();
        await expect(page.getByLabel('Income', { exact: true })).not.toBeChecked();
        await expect(page.getByLabel('Allow creating accounts')).toBeChecked();
        await expect(page.getByLabel('Allow creating categories')).toBeChecked();
        await page.getByRole('link', { name: 'Webhook logs' }).click();
        await expect(page).toHaveURL(/\/webhooks\/\d+\/logs(\?.*)?$/);
        await expect(page.getByRole('heading', { name: 'Webhook logs' })).toBeVisible();
        await page.getByRole('link', { name: 'Back to edit webhook' }).click();
        await page.getByRole('link', { name: 'Test webhook' }).click();
        await expect(page).toHaveURL(/\/webhooks\/api-docs\?webhook=\d+$/);
        await expect(page.getByRole('combobox', { name: 'Webhook' })).toHaveValue(new RegExp(mainValue));
        await expect(page.locator('[data-try-preview]')).toContainText('"signature"');

        await page.getByRole('button', { name: 'Execute' }).click();
        await expect(page.getByText('Account name is required.')).toBeVisible();

        await page.getByLabel('Account name').fill(`${mainValue} account`);
        await page.getByRole('button', { name: 'Execute' }).click();
        await expect(page.locator('[data-try-result]')).toContainText('X-Webhook-Nonce');
        await expect(page.locator('[data-try-url]')).toHaveText(/\/api\/webhooks\/\d+\/test$/);
        await page.getByLabel('Production mode').check();
        await expect(page.locator('#testWebhook > div').first().locator('code')).toHaveText('/webhooks/records');
        await page.getByRole('button', { name: 'Execute' }).click();
        await expect(page.locator('[data-try-url]')).toHaveText(/\/api\/webhooks\/records$/);
        await page.goBack();
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
        await expect(page).toHaveURL(/\/webhooks\/api-docs\?webhook=\d+$/);
        await page.getByRole('link', { name: 'Manage Webhooks' }).first().click();
        await expect(page).toHaveURL('/webhooks');
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
