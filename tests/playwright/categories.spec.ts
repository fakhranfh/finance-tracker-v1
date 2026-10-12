import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

test.describe('Manage Categories', () => {
    test('lists categories and navigates to the create page (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.getByRole('link', { name: 'Manage Categories' }).click();

        await expect(page).toHaveURL('/categories');
        await expect(page.getByRole('heading', { name: 'Manage Categories' })).toBeVisible();
        await expect(page.getByRole('table')).toBeVisible();

        await page.getByRole('link', { name: 'Add category' }).click();
        await expect(page).toHaveURL('/categories/create');
    });

    test('creates, edits, and deletes a category end-to-end', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/categories/create');

        await page.getByLabel('Name', { exact: true }).fill(`${mainValue}`);
        await page.getByText('Income', { exact: true }).click();
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/categories');
        await expect(page.getByText('Category created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();
        await expect(row.locator('td svg.size-6')).toBeVisible();

        await row.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/categories');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await editedRow.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this category?')).toBeVisible();
        await page.locator('#confirm-delete-category').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('assigns a category to a record', async ({ page }) => {
        const { email, password } = seedUser();
        const name = `E2E Food ${Date.now()}`;
        const note = `E2E note ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/accounts/create');
        await page.getByLabel('Name', { exact: true }).fill('E2E Cash');
        await page.getByLabel('Balance', { exact: true }).fill('100000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/accounts');

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(name);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/records/create');
        await page.getByLabel('Account', { exact: true }).selectOption({ label: 'E2E Cash' });
        await page.getByLabel('Category', { exact: true }).fill(name.slice(-8));
        await page.getByRole('option', { name }).click();
        await page.getByLabel('Amount', { exact: true }).fill('5000');
        await page.getByLabel('Note', { exact: true }).fill(note);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/records');
        await expect(page.getByRole('row', { name: new RegExp(`${name}.*${note}`) })).toBeVisible();
    });

    test('shows Uncategorized for a record without a category', async ({ page }) => {
        const { email, password } = seedUser();
        const note = `E2E plain ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/accounts/create');
        await page.getByLabel('Name', { exact: true }).fill('E2E Cash');
        await page.getByLabel('Balance', { exact: true }).fill('100000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/accounts');

        await page.goto('/records/create');
        await page.getByLabel('Account', { exact: true }).selectOption({ label: 'E2E Cash' });
        await page.getByLabel('Amount', { exact: true }).fill('5000');
        await page.getByLabel('Note', { exact: true }).fill(note);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/records');
        await expect(page.getByRole('row', { name: new RegExp(`Uncategorized.*${note}`) })).toBeVisible();

        await page.locator('#filter-category_ids summary').click();
        await page.locator('#filter-category_ids').getByText('Uncategorized').click();
        await expect(page.getByRole('row', { name: new RegExp(note) })).toBeVisible();
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/categories');

        await expect(page).toHaveURL(/\/login$/);
    });
});
