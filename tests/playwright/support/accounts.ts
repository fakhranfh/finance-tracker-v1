import { expect, type Page } from '@playwright/test';

/** Debts need an account, so specs create one first. */
export async function ensureAccount(page: Page): Promise<string> {
    const name = `E2E Account ${Date.now()}`;

    await page.goto('/accounts/create');
    await page.getByLabel('Name', { exact: true }).fill(name);
    await page.getByLabel('Balance', { exact: true }).fill('500000');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page).toHaveURL('/accounts');

    return name;
}

export async function pickDebtAccount(page: Page, account: string) {
    await expect(page.locator('select[name="account_id"] option', { hasText: account })).toHaveCount(1);
    await page.getByLabel('Account', { exact: true }).fill(account);
    await page.getByRole('option', { name: account }).click();
}

/** A new debt asks whether to also record the money moving in or out of the account. */
export async function answerRecordPrompt(page: Page, createRecord = false) {
    await page.getByRole('button', { name: createRecord ? 'Yes, create record' : 'No, just save' }).click();
}
