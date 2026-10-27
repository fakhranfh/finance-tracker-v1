import { test, expect, Page } from '@playwright/test';
import { seedUser } from './support/seed';
import { answerRecordPrompt, ensureAccount, pickDebtAccount } from './support/accounts';

async function login(page: Page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

async function createAccount(page: Page, name: string) {
    await page.goto('/accounts/create');
    await page.getByLabel('Name', { exact: true }).fill(name);
    await page.getByLabel('Balance', { exact: true }).fill('500000');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page).toHaveURL('/accounts');
}

async function createDebt(page: Page, path: string, name: string, amount: string) {
    const debtAccount = await ensureAccount(page);
    await page.goto(`${path}/create`);
    await pickDebtAccount(page, debtAccount);
    await page.getByLabel('Name', { exact: true }).first().fill(name);
    await page.locator('#amount').fill(amount);
    await page.getByRole('button', { name: 'Save' }).click();
await answerRecordPrompt(page);
    await expect(page).toHaveURL(path);
}

test.describe('Debt payments', () => {
    test('repays a debt with a new record and with an existing one', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();
        const account = `E2E Cash ${stamp}`;
        const debt = `E2E Debt ${stamp}`;

        await login(page, email, password);
        await createAccount(page, account);

        await page.goto('/records/create');
        await page.getByText('Expense', { exact: true }).click();
        await page.getByLabel('Account', { exact: true }).selectOption({ label: account });
        await page.getByLabel('Amount', { exact: true }).fill('30000');
        await page.getByLabel('Note', { exact: true }).fill(`Bayar utang ${stamp}`);
        await page.getByLabel('Date', { exact: true }).fill('2026-01-15T10:30');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/records');

        await createDebt(page, '/debts', debt, '100000');
        await page.getByRole('row', { name: new RegExp(debt) }).getByRole('link', { name: 'Payments' }).click();
        await expect(page).toHaveURL(/\/debts\/\d+\/payments$/);
        await expect(page.getByRole('heading', { name: 'Payments' })).toBeVisible();
        await expect(page.getByText('No payments yet.')).toBeVisible();

        // A new record made right from the debt.
        await page.getByRole('link', { name: 'Add payment' }).click();
        await expect(page).toHaveURL(/\/debts\/\d+\/payments\/create$/);
        await expect(page.getByLabel('Amount', { exact: true })).toHaveValue('100.000');
        await page.getByLabel('Account', { exact: true }).selectOption({ label: account });
        await page.getByLabel('Amount', { exact: true }).fill('40000');
        await page.getByLabel('Note', { exact: true }).fill(`Cicilan ${stamp}`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/debts\/\d+\/payments$/);
        await expect(page.getByText('Payment added successfully.')).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(`Cicilan ${stamp}`) })).toContainText('40.000');
        await expect(page.locator('[data-payments-summary]')).toContainText('40.000');

        // An existing record picked from the records.
        await page.getByRole('link', { name: 'Use an existing record' }).click();
        await expect(page).toHaveURL(/\/debts\/\d+\/payments\/existing$/);
        await page.getByRole('row', { name: new RegExp(`Bayar utang ${stamp}`) }).getByRole('button', { name: 'Use this record' }).click();

        await expect(page).toHaveURL(/\/debts\/\d+\/payments$/);
        await expect(page.getByText('Record added as a payment.')).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(`Bayar utang ${stamp}`) })).toBeVisible();
        await expect(page.locator('[data-payments-summary]')).toContainText('70.000');

        // Nothing can be paid beyond what is still owed.
        await page.getByRole('link', { name: 'Add payment' }).click();
        await page.getByLabel('Account', { exact: true }).selectOption({ label: account });
        await page.getByLabel('Amount', { exact: true }).fill('50000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText(/more than what is still owed/)).toBeVisible();
        await page.getByRole('link', { name: 'Cancel' }).click();

        // Removing a payment keeps its record.
        await page.getByRole('row', { name: new RegExp(`Cicilan ${stamp}`) }).getByRole('button', { name: 'Remove payment' }).click();
        await expect(page.getByText('Stop counting this record as a payment?')).toBeVisible();
        await page.locator('#confirm-delete-payment').click();
        await expect(page.getByRole('row', { name: new RegExp(`Cicilan ${stamp}`) })).toHaveCount(0);
        await expect(page.locator('[data-payments-summary]')).toContainText('30.000');

        await page.goto('/debts');
        await expect(page.getByRole('row', { name: new RegExp(debt) })).toContainText('30.000');
    });

    test('pays a sub debt off and marks it paid', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();
        const account = `E2E Cash ${stamp}`;
        const debt = `E2E Debt ${stamp}`;

        await login(page, email, password);
        await createAccount(page, account);

        const debtAccount = await ensureAccount(page);
        await page.goto('/debts/create');
        await pickDebtAccount(page, debtAccount);
        await page.getByLabel('Name', { exact: true }).first().fill(debt);
        await page.getByLabel('Split into sub debts').check();
        await page.locator('[name="sub_debts.0.name"]').fill('Uang muka');
        await page.locator('[name="sub_debts.0.amount"]').fill('100000');
        await page.getByRole('button', { name: 'Add sub debt' }).click();
        await page.locator('[name="sub_debts.1.name"]').fill('Pelunasan');
        await page.locator('[name="sub_debts.1.amount"]').fill('200000');
        await page.getByRole('button', { name: 'Save' }).click();
await answerRecordPrompt(page);
        await expect(page).toHaveURL('/debts');

        await page.getByRole('row', { name: new RegExp(debt) }).getByRole('link', { name: 'Sub debts' }).click();
        await page.getByRole('row', { name: /Uang muka/ }).getByRole('link', { name: 'Payments' }).click();
        await expect(page).toHaveURL(/\/debts\/\d+\/payments\?sub_debt_id=\d+$/);

        await page.getByRole('link', { name: 'Add payment' }).click();
        await expect(page).toHaveURL(/\/payments\/create\?sub_debt_id=\d+$/);
        await expect(page.getByLabel('Sub debt')).toHaveValue(/\d+/);
        await expect(page.getByLabel('Amount', { exact: true })).toHaveValue('100.000');

        await page.getByLabel('Sub debt').selectOption('');
        await page.getByLabel('Account', { exact: true }).selectOption({ label: account });
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Choose which sub debt this payment is for.')).toBeVisible();

        await page.getByLabel('Sub debt').selectOption({ label: 'Uang muka (Rp 100.000)' });
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/debts\/\d+\/payments$/);
        await expect(page.getByRole('row', { name: /Uang muka/ })).toContainText('100.000');

        await page.goto('/debts');
        await page.getByRole('row', { name: new RegExp(debt) }).getByRole('link', { name: 'Sub debts' }).click();
        await expect(page.getByRole('row', { name: /Uang muka/ })).toContainText('Paid');
        await expect(page.getByRole('row', { name: /Pelunasan/ })).toContainText('Not paid');
        await expect(page.locator('[data-sub-debts-summary]')).toContainText('1 of 2 sub debts paid');
    });

    test('settles a receivable with income records', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();
        const account = `E2E Cash ${stamp}`;
        const receivable = `E2E Receivable ${stamp}`;

        await login(page, email, password);
        await createAccount(page, account);
        await createDebt(page, '/receivables', receivable, '80000');

        await page.getByRole('row', { name: new RegExp(receivable) }).getByRole('link', { name: 'Payments' }).click();
        await expect(page).toHaveURL(/\/receivables\/\d+\/payments$/);
        await expect(page.getByRole('heading', { name: 'Payments received' })).toBeVisible();

        await page.getByRole('link', { name: 'Add payment received' }).click();
        await page.getByLabel('Account', { exact: true }).selectOption({ label: account });
        await page.getByLabel('Note', { exact: true }).fill(`Diterima ${stamp}`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/receivables\/\d+\/payments$/);
        await expect(page.getByRole('row', { name: new RegExp(`Diterima ${stamp}`) })).toContainText('80.000');
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/debts/1/payments');
        await expect(page).toHaveURL(/\/login/);
    });
});
