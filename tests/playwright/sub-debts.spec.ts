import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Sub debts', () => {
    test('splits a debt into sub debts and edits them together', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/debts/create');

        await page.getByLabel('Name', { exact: true }).first().fill(name);
        await page.getByLabel('Split into sub debts').check();

        await page.locator('[name="sub_debts.0.name"]').fill('Uang muka');
        await page.locator('[name="sub_debts.0.amount"]').fill('100000');

        await page.getByRole('button', { name: 'Add sub debt' }).click();
        await page.locator('[name="sub_debts.1.name"]').fill('Pelunasan');
        await page.locator('[name="sub_debts.1.amount"]').fill('200000');

        await expect(page.locator('#sub-debts [data-sub-debts-totals]')).toContainText('300.000');
        await expect(page.locator('#amount')).toHaveValue('300.000');
        await expect(page.locator('#paid_amount')).toHaveCount(0);

        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/debts');

        const row = page.getByRole('row', { name: new RegExp(name) });
        await expect(row).toContainText('300.000');
        await row.getByRole('link', { name: 'Sub debts' }).click();

        await expect(page).toHaveURL(/\/debts\/\d+\/sub-debts$/);
        await expect(page.getByRole('heading', { name: 'Sub debts' })).toBeVisible();
        await expect(page.getByRole('row', { name: /Uang muka/ })).toContainText('Not paid');
        await expect(page.locator('[data-sub-debts-summary]')).toContainText('0 of 2 sub debts paid');

        const listUrl = page.url();
        await page.goto(`${listUrl}/edit`);
        await expect(page).toHaveURL(/\/debts\/\d+\/sub-debts\/edit$/);
        await expect(page.getByRole('heading', { name: 'Edit sub debts' })).toBeVisible();
        await expect(page.locator('[name="sub_debts.1.name"]')).toHaveValue('Pelunasan');
        await expect(page.locator('#sub-debts [data-sub-debts-totals]')).toContainText('300.000');

        await page.locator('[name="sub_debts.1.name"]').fill('Pelunasan akhir');
        await page.getByRole('button', { name: 'Add sub debt' }).click();
        await page.locator('[name="sub_debts.2.name"]').fill('Biaya admin');
        await page.locator('[name="sub_debts.2.amount"]').fill('10000');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/debts\/\d+\/sub-debts$/);
        await expect(page.getByRole('row', { name: /Pelunasan akhir/ })).toBeVisible();
        await expect(page.getByRole('row', { name: /Biaya admin/ })).toContainText('Not paid');
        await expect(page.locator('[data-sub-debts-summary]')).toContainText('0 of 3 sub debts paid');

        await page.getByRole('row', { name: /Uang muka/ }).getByRole('link', { name: 'Edit' }).click();
        await expect(page).toHaveURL(/\/debts\/\d+\/sub-debts\/\d+\/edit$/);
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue('Uang muka');

        await page.goto(listUrl.replace('/sub-debts', '/edit'));
        await expect(page.locator('[name="sub_debts.2.name"]')).toHaveValue('Biaya admin');
        await expect(page.locator('#amount')).toHaveValue('310.000');

        await page.goto('/debts');
        await expect(page.getByRole('row', { name: new RegExp(name) })).toContainText('310.000');
    });

    test('lets a sub debt reuse the due date of its debt', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/debts/create');

        await page.getByLabel('Name', { exact: true }).first().fill(name);
        await page.getByLabel('Split into sub debts').check();
        await page.locator('[name="sub_debts.0.name"]').fill('Cicilan 1');
        await page.locator('[name="sub_debts.0.amount"]').fill('100000');

        const same = page.getByLabel('Same as the main due date').first();
        await expect(same).toBeDisabled();

        await page.locator('#due_date').fill('2026-12-31');
        await expect(same).toBeEnabled();
        await expect(same).toBeChecked();
        await expect(page.locator('[name="sub_debts.0.due_date"]')).toHaveValue('2026-12-31');
        await expect(page.locator('[name="sub_debts.0.due_date"]')).toBeDisabled();

        await page.locator('#due_date').fill('2027-01-15');
        await expect(page.locator('[name="sub_debts.0.due_date"]')).toHaveValue('2027-01-15');

        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/debts');

        await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'Sub debts' }).click();
        await expect(page.getByRole('row', { name: /Cicilan 1/ })).toContainText('Jan 15, 2027');

        await page.goto(`${page.url()}/edit`);
        await expect(page.getByLabel('Same as the main due date').first()).toBeChecked();
    });

    test('adds, edits and deletes a single sub debt from the list', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/debts/create');
        await page.getByLabel('Name', { exact: true }).first().fill(name);
        await page.locator('#amount').fill('500000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/debts');

        await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'Sub debts' }).click();
        await expect(page.getByRole('link', { name: 'Edit sub debts' })).toHaveCount(0);

        await page.getByRole('link', { name: 'Add sub debt' }).click();
        await expect(page).toHaveURL(/\/debts\/\d+\/sub-debts\/create$/);
        await page.getByLabel('Name', { exact: true }).fill('Cicilan motor');
        await page.getByLabel('Amount', { exact: true }).fill('120000');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/debts\/\d+\/sub-debts$/);
        await expect(page.getByText('Sub debt created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: /Cicilan motor/ });
        await expect(row).toContainText('120.000');
        await expect(page.locator('[data-sub-debts-summary]')).toContainText('0 of 1 sub debts paid');

        await row.getByRole('link', { name: 'Edit' }).click();
        await page.getByLabel('Name', { exact: true }).fill('Cicilan motor Honda');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.getByRole('row', { name: /Cicilan motor Honda/ })).toBeVisible();

        await page.getByRole('row', { name: /Cicilan motor Honda/ }).getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this sub debt?')).toBeVisible();
        await page.locator('#confirm-delete-sub-debt').click();
        await expect(page.getByRole('row', { name: /Cicilan motor Honda/ })).toHaveCount(0);
    });

    test('shows sub receivables under the receivables tab', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/receivables/create');

        await page.getByLabel('Name', { exact: true }).first().fill(name);
        await page.getByLabel('Split into sub debts').check();
        await page.locator('[name="sub_debts.0.name"]').fill('Cicilan 1');
        await page.locator('[name="sub_debts.0.amount"]').fill('50000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/receivables');

        await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'Sub receivables' }).click();
        await expect(page).toHaveURL(/\/receivables\/\d+\/sub-debts$/);
        await expect(page.getByRole('heading', { name: 'Sub receivables' })).toBeVisible();
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/debts/1/sub-debts');
        await expect(page).toHaveURL(/\/login/);
    });
});
