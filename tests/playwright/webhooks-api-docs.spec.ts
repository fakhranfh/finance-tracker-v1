import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

/** Creates a webhook through the UI and returns its id. */
async function createWebhook(page, name: string): Promise<string> {
    await page.goto('/webhooks/create');
    await page.getByLabel('Name', { exact: true }).fill(name);
    await page.getByLabel('Is Active', { exact: true }).check();
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page).toHaveURL('/webhooks');
    await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'View' }).click();
    await expect(page).toHaveURL(/\/webhooks\/\d+$/);

    return page.url().match(/\/webhooks\/(\d+)$/)![1];
}

test.describe('Webhook API docs', () => {
    test('shows the operations and the try it out builder (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.goto('/webhooks/api-docs');

        await expect(page.getByRole('heading', { name: 'Webhook API' })).toBeVisible();
        await expect(page.getByRole('link', { name: /Manage Webhooks/ }).first()).toHaveAttribute('href', /\/webhooks$/);
        await expect(page.locator('#testWebhook')).toBeVisible();
        await expect(page.locator('#webhook-api-operations [data-try-it-out]').first()).toBeVisible();
        await expect(page.getByLabel('Account name')).toBeVisible();
        await expect(page.getByLabel('Amount')).toBeVisible();
        await expect(page.getByLabel('Production mode')).not.toBeChecked();
        await expect(page.locator('[data-try-response]').first()).toBeHidden();
    });

    test('validates required fields before sending', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.goto('/webhooks/api-docs');

        await page.getByRole('button', { name: 'Execute' }).click();

        await expect(page.getByText('Account name is required.')).toBeVisible();
        await expect(page.locator('[data-try-response]').first()).toBeHidden();
    });

    test('preselects the webhook, signs the preview, and executes the request', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `E2E Docs ${Date.now()}`;

        await login(page, email, password);
        const id = await createWebhook(page, name);

        await page.goto(`/webhooks/api-docs?webhook=${id}`);

        await expect(page.getByRole('combobox', { name: 'Webhook' })).toHaveValue(new RegExp(name));
        await expect(page.locator('[data-try-preview]')).toContainText('"signature"');

        await page.locator('[data-try-note]').fill('Hello e2e');
        await expect(page.locator('[data-try-preview]')).toContainText('Hello e2e');

        await page.getByLabel('Account name').fill(`${name} account`);
        await page.getByRole('button', { name: 'Execute' }).click();

        await expect(page.locator('[data-try-response]').first()).toBeVisible();
        await expect(page.locator('[data-try-url]')).toHaveText(new RegExp(`/api/webhooks/${id}/test$`));
        await expect(page.locator('[data-try-code]')).not.toHaveText('');
        await expect(page.locator('[data-try-result]')).toContainText('X-Webhook-Timestamp');
        await expect(page.locator('[data-try-result]')).toContainText('X-Webhook-Nonce');
    });

    test('production mode switches to the real endpoint path', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.goto('/webhooks/api-docs');

        const path = page.locator('#testWebhook > div').first().locator('code');
        const testPath = await path.textContent();

        await page.getByLabel('Production mode').check();
        await expect(path).toHaveText('/webhooks/records');

        await page.getByLabel('Production mode').uncheck();
        await expect(path).toHaveText(testPath!);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/webhooks/api-docs');

        await expect(page).toHaveURL(/\/login$/);
    });
});
