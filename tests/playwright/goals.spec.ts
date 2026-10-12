import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Goals', () => {
    test('lists goals and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.getByRole('link', { name: 'Manage Goals' }).click();

        await expect(page).toHaveURL('/goals');
        await expect(page.getByRole('heading', { name: 'Manage Goals' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();

        await page.getByRole('link', { name: 'Add goal' }).click();
        await expect(page).toHaveURL('/goals/create');
    });

    test('creates, edits, and deletes a goal end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/goals/create');

        await page.getByLabel('Name', { exact: true }).fill(`${mainValue}`);
        await page.getByLabel('Target Amount', { exact: true }).fill('15000000');
        await page.getByLabel('Saved Amount', { exact: true }).fill('2500000');
        await page.getByLabel('Desired Date', { exact: true }).fill('2027-03-15');
        await page.locator('input[name="color"]').fill('#10b981');
        await page.getByTitle('Home').click();
        await page.getByLabel('Status', { exact: true }).selectOption('paused');
        await page.getByLabel('Note', { exact: true }).fill('E2E Note');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/goals');
        await expect(page.getByText('Goal created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();
        await expect(row).toContainText('Mar 15, 2027');
        await expect(row).toContainText('Paused');
        await expect(row.locator('td svg.size-6')).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await expect(page.getByLabel('Saved Amount', { exact: true })).toHaveValue('2500000.00');
        await expect(page.getByLabel('Status', { exact: true })).toHaveValue('paused');
        await page.getByLabel('Status', { exact: true }).selectOption('reached');
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/goals');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await expect(editedRow).toContainText('Reached');
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this goal?')).toBeVisible();
        await page.locator('#confirm-delete-goal').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/goals');

        await expect(page).toHaveURL(/\/login$/);
    });
});
