import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';
import { answerRecordPrompt, ensureAccount, pickDebtAccount } from './support/accounts';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Debts', () => {
    test('lists debts and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Debts & Receivables' }).click();

        await expect(page).toHaveURL('/debts');
        await expect(page.getByRole('heading', { name: 'Manage Debts' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();

        await page.getByRole('link', { name: 'Add debt' }).click();
        await expect(page).toHaveURL('/debts/create');
    });

    test('creates, edits, and deletes a debt end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        const debtAccount = await ensureAccount(page);
        await page.goto('/debts/create');
        await pickDebtAccount(page, debtAccount);

        await page.getByLabel('Name', { exact: true }).fill(`${mainValue}`);
        await page.getByLabel('Amount', { exact: true }).fill('150000');
        await page.getByLabel('Due Date', { exact: true }).fill('2026-01-15');
        await page.getByLabel('Notes', { exact: true }).fill('E2E Notes');
        await page.getByRole('button', { name: 'Save' }).click();
await answerRecordPrompt(page, true);

        await expect(page).toHaveURL('/debts');
        await expect(page.getByText('Debt created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/debts');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this debt?')).toBeVisible();
        await page.locator('#confirm-delete-debt').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('forgives a debt from the list', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E Forgive ${Date.now()}`;

        await login(page, email, password);
        const debtAccount = await ensureAccount(page);
        await page.goto('/debts/create');
        await pickDebtAccount(page, debtAccount);

        await page.getByLabel('Name', { exact: true }).fill(mainValue);
        await page.getByLabel('Amount', { exact: true }).fill('75000');
        await page.getByRole('button', { name: 'Save' }).click();
        await answerRecordPrompt(page);
        await expect(page).toHaveURL('/debts');

        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await row.getByRole('button', { name: 'Forgive' }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Forgive' }).click();

        await expect(page.getByText('Debt forgiven successfully.')).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(mainValue) }).getByRole('button', { name: 'Forgive' })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/debts');

        await expect(page).toHaveURL(/\/login$/);
    });
});
