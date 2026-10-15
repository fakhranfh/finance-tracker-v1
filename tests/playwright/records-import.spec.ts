import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

async function createAccount(page, name: string) {
    await page.goto('/accounts/create');
    await page.getByLabel('Name', { exact: true }).fill(name);
    await page.getByLabel('Balance', { exact: true }).fill('100000');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page).toHaveURL('/accounts');
}

function csvFile(rows: string[], name = 'records.csv') {
    return { name, mimeType: 'text/csv', buffer: Buffer.from(rows.join('\n')) };
}

test.describe('Import records', () => {
    test('imports a file after mapping columns, accounts and categories', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();

        await login(page, email, password);
        await createAccount(page, `E2E Cash ${stamp}`);

        await page.goto('/records');
        await page.getByRole('link', { name: 'Import Excel' }).click();
        await expect(page).toHaveURL('/records/import');
        await expect(page.getByRole('heading', { name: 'Import records' })).toBeVisible();

        await page.locator('#file').setInputFiles(csvFile([
            'Date,Type,Account,Category,Amount,Note',
            `2026-10-01 08:00:00,expense,E2E Cash ${stamp},Imported Food,25000,e2e import lunch ${stamp}`,
            `2026-10-02 09:00:00,income,Imported Bank ${stamp},,1000000,e2e import salary ${stamp}`,
        ]));
        await page.getByRole('button', { name: 'Upload and continue' }).click();

        const mapping = (field: string) => page.locator(`[data-import-field="${field}"]`);
        await expect(page.getByText('Step 2 of 4')).toBeVisible();
        await expect(mapping('recorded_at')).toHaveValue('0');
        await expect(mapping('account')).toHaveValue('2');
        await expect(mapping('amount')).toHaveValue('4');
        await page.getByRole('button', { name: 'Continue', exact: true }).click();

        await expect(page.getByText('Step 3 of 4')).toBeVisible();
        const choice = (value: string) => page.locator(`select[data-value="${value}"]`);
        await expect(page.locator('#import-type-expense')).toHaveValue('expense');
        await expect(page.locator('#import-type-income')).toHaveValue('income');
        await page.locator('#import-type-income').fill('income, Salary');
        await expect(choice(`E2E Cash ${stamp}`)).not.toHaveValue('create');
        await expect(choice(`Imported Bank ${stamp}`)).toHaveValue('create');
        await expect(choice('Imported Food')).toHaveValue('create');
        await expect(page.getByLabel(`Imported Bank ${stamp}`, { exact: true })).toHaveValue(`Create new account "Imported Bank ${stamp}"`);

        // The dropdown is searchable: type to filter and pick the unmapped account.
        await page.getByLabel(`Imported Bank ${stamp}`, { exact: true }).fill('unmapped');
        await page.getByRole('option', { name: 'Unmapped account' }).click();
        await expect(choice(`Imported Bank ${stamp}`)).toHaveValue('unmapped');
        await page.getByLabel(`Imported Bank ${stamp}`, { exact: true }).fill('create');
        await page.getByRole('option', { name: /Create new account/ }).click();
        await page.getByRole('button', { name: 'Import records' }).click();

        await expect(page.getByText('2 records imported.')).toBeVisible();
        await expect(page.getByText(`New accounts created: Imported Bank ${stamp}.`)).toBeVisible();
        await expect(page.getByText('New categories created: Imported Food.')).toBeVisible();

        await page.getByRole('link', { name: 'View records' }).click();
        await expect(page).toHaveURL('/records');
        await expect(page.getByRole('row', { name: new RegExp(`e2e import lunch ${stamp}`) })).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(`e2e import salary ${stamp}`) })).toBeVisible();
    });

    test('sends records to the Unmapped account when no account column is mapped', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();

        await login(page, email, password);
        await page.goto('/records/import');

        await page.locator('#file').setInputFiles(csvFile([
            'Date,Amount,Note',
            `2026-10-03,-5000,e2e unmapped ${stamp}`,
        ]));
        await page.getByRole('button', { name: 'Upload and continue' }).click();
        await page.getByRole('button', { name: 'Continue', exact: true }).click();

        await expect(page.getByText('every record goes to the "Unmapped" account')).toBeVisible();
        await page.getByRole('button', { name: 'Import records' }).click();
        await expect(page.getByText('1 records imported.')).toBeVisible();

        await page.getByRole('link', { name: 'View records' }).click();
        await expect(page.getByRole('row', { name: new RegExp(`e2e unmapped ${stamp}`) })).toContainText('Unmapped');
    });

    test('keeps progress on the server and lets several imports stay open', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();
        const firstName = `first-${stamp}.csv`;
        const secondName = `second-${stamp}.csv`;

        await login(page, email, password);
        await page.goto('/records/import');

        await page.locator('#file').setInputFiles(csvFile(['Date,Account,Amount', `2026-10-05,Resume Bank ${stamp},1000`], firstName));
        await page.getByRole('button', { name: 'Upload and continue' }).click();
        await expect(page.getByText('Step 2 of 4')).toBeVisible();
        await expect(page).toHaveURL(/\/records\/import\?import=\d+$/);

        // A reload resumes the same import at the same step.
        await page.reload();
        await expect(page.getByText('Step 2 of 4')).toBeVisible();
        await expect(page.locator('[data-import-field="account"]')).toHaveValue('1');

        await page.getByRole('button', { name: 'Continue', exact: true }).click();
        await expect(page.getByText('Step 3 of 4')).toBeVisible();
        await page.getByLabel(`Resume Bank ${stamp}`, { exact: true }).fill('unmapped');
        await page.getByRole('option', { name: 'Unmapped account' }).click();
        // Wait for the choice to be saved before leaving.
        await page.waitForResponse((response) => response.request().method() === 'PUT' && response.url().includes('/api/records/imports/'));

        // Start a second import while the first is unfinished.
        await page.goto('/records/import');
        await expect(page.getByRole('heading', { name: 'Imports in progress' })).toBeVisible();
        await expect(page.getByText(firstName)).toBeVisible();

        await page.locator('#file').setInputFiles(csvFile(['Date,Amount', '2026-10-06,500'], secondName));
        await page.getByRole('button', { name: 'Upload and continue' }).click();
        await expect(page.getByText('Step 2 of 4')).toBeVisible();

        await page.getByRole('button', { name: 'Back', exact: true }).click();
        await expect(page.getByText('Step 1 of 4')).toBeVisible();
        await expect(page.getByText(firstName)).toBeVisible();
        await expect(page.getByText(secondName)).toBeVisible();

        // Discard the second one.
        await page.getByRole('button', { name: `Discard: ${secondName}` }).click();
        await page.locator('#confirm-discard-import').click();
        await expect(page.getByText(secondName)).toHaveCount(0);

        // The first one is still at step 3 with its choice.
        await page.getByRole('button', { name: `Resume: ${firstName}` }).click();
        await expect(page.getByText('Step 3 of 4')).toBeVisible();
        await expect(page.locator(`select[data-value="Resume Bank ${stamp}"]`)).toHaveValue('unmapped');

        await page.getByRole('button', { name: 'Import records' }).click();
        await expect(page.getByText('1 records imported.')).toBeVisible();

        await page.goto('/records/import');
        await expect(page.getByText(firstName)).toHaveCount(0);
    });

    test('requires the date and amount columns', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.goto('/records/import');

        await page.getByRole('button', { name: 'Upload and continue' }).click();
        await expect(page.locator('[data-error-for="file"]')).toBeVisible();

        await page.locator('#file').setInputFiles(csvFile(['Foo,Bar', 'a,b']));
        await page.getByRole('button', { name: 'Upload and continue' }).click();
        await page.getByRole('button', { name: 'Continue', exact: true }).click();

        await expect(page.locator('[data-error-for="mapping.recorded_at"]')).toBeVisible();
        await expect(page.locator('[data-error-for="mapping.amount"]')).toBeVisible();
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/records/import');

        await expect(page).toHaveURL(/\/login$/);
    });
});
