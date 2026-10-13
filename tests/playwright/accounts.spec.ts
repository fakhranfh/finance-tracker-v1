import { test, expect } from '@playwright/test';
import { resetDatabase, seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Accounts', () => {
    test('lists accounts and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Manage Accounts' }).click();

        await expect(page).toHaveURL('/accounts');
        await expect(page.getByRole('heading', { name: 'Manage Accounts' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();

        await page.getByRole('link', { name: 'Add account' }).click();
        await expect(page).toHaveURL('/accounts/create');
    });

    test('creates, edits, and deletes a account end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/accounts/create');

        await page.getByLabel('Name', { exact: true }).fill(`${mainValue}`);
        await page.getByLabel('Balance', { exact: true }).fill('12500');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/accounts');
        await expect(page.getByText('Account created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/accounts');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this account?')).toBeVisible();
        await page.locator('#confirm-delete-account').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('saves a chosen color and icon, then shows them when editing', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `Color ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/accounts/create');

        await page.getByLabel('Name', { exact: true }).fill(mainValue);
        await page.getByLabel('Balance', { exact: true }).fill('5000');
        await page.locator('input[name="color"]').fill('#ff1744');
        await page.locator('input[name="icon"][value="bank"]').check({ force: true });
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/accounts');
        await page.getByRole('row', { name: new RegExp(mainValue) }).getByRole('link', { name: 'Edit' }).click();

        await expect(page.locator('input[name="color"]')).toHaveValue('#ff1744');
        await expect(page.locator('input[name="icon"][value="bank"]')).toBeChecked();
    });

    test('moves an account up in the arranged order and keeps it after reopening', async ({ page }) => {
        resetDatabase();
        const { email, password } = seedUser();

        await login(page, email, password);

        for (const name of ['Order A', 'Order B']) {
            await page.goto('/accounts/create');
            await page.getByLabel('Name', { exact: true }).fill(name);
            await page.getByLabel('Balance', { exact: true }).fill('1000');
            await page.getByRole('button', { name: 'Save' }).click();
            await expect(page).toHaveURL('/accounts');
        }

        await page.goto('/accounts');
        await page.getByRole('button', { name: 'Arrange order' }).click();

        const modal = page.locator('#reorder-accounts-modal');
        await expect(modal).toBeVisible();
        await expect(modal.locator('#reorder-accounts-list li').first()).toContainText('Order A');

        await modal.locator('#reorder-accounts-list li', { hasText: 'Order B' }).getByRole('button', { name: 'Move up' }).click();
        await expect(modal.locator('#reorder-accounts-list li').first()).toContainText('Order B');

        await modal.getByRole('button', { name: 'Save' }).click();
        await expect(modal).toBeHidden();

        await page.getByRole('button', { name: 'Arrange order' }).click();
        await expect(modal.locator('#reorder-accounts-list li').first()).toContainText('Order B');
    });

    test('shows the account name on the summary page', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `Summary ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/accounts/create');
        await page.getByLabel('Name', { exact: true }).fill(name);
        await page.getByLabel('Balance', { exact: true }).fill('1000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/accounts');

        await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'View' }).click();

        await expect(page).toHaveURL(/\/accounts\/\d+\/summary(\?.*)?$/);
        await expect(page.getByRole('heading', { name })).toBeVisible();
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/accounts');

        await expect(page).toHaveURL(/\/login$/);
    });
});
