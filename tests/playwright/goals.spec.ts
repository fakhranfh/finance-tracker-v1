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
        await page.getByLabel('Paused', { exact: true }).check();
        await page.getByLabel('Note', { exact: true }).fill('E2E Note');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/goals');
        await expect(page.getByText('Goal created successfully.')).toBeVisible();
        const row = page.getByRole('row', { name: new RegExp(mainValue) });
        await expect(row).toBeVisible();
        await expect(row).toContainText('Mar 15, 2027');
        await expect(row.locator('select')).toHaveValue('paused');
        await expect(row.locator('td svg.size-6')).toBeVisible();

        await row.getByRole('link', { name: 'View' }).click();
        await expect(page).toHaveURL(/\/goals\/\d+$/);
        await expect(page.getByRole('heading', { name: 'Goal details' })).toBeVisible();
        await expect(page.getByRole('heading', { name: mainValue })).toBeVisible();
        await expect(page.getByText('Mar 15, 2027')).toBeVisible();
        await expect(page.getByText('E2E Note')).toBeVisible();
        await expect(page.getByText('Estimated Completion')).toBeVisible();
        await expect(page.getByText('Remaining')).toHaveCount(0);

        await page.getByRole('button', { name: 'Add saved amount' }).click();
        await page.getByLabel('Amount to add', { exact: true }).fill('500000');
        await page.getByRole('dialog').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Saved amount updated.')).toBeVisible();
        await expect(page.getByText(/3\.000\.000/).first()).toBeVisible();

        await page.getByRole('link', { name: 'Edit' }).click();

        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(mainValue);
        await expect(page.getByLabel('Saved Amount', { exact: true })).toHaveValue('3.000.000');
        await expect(page.getByLabel('Paused', { exact: true })).toBeChecked();
        await page.getByLabel('Name', { exact: true }).fill(`${mainValue} Edited`);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/goals');
        const editedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(editedRow).toBeVisible();
        await expect(editedRow.locator('select')).toHaveValue('paused');

        await editedRow.locator('select').selectOption('active');
        await page.reload();
        await expect(page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) }).locator('select')).toHaveValue('active');

        await page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) }).getByRole('link', { name: 'View' }).click();
        await page.getByRole('button', { name: 'Set as reached' }).click();
        await expect(page.getByText('Once a goal is reached it becomes read-only.')).toBeVisible();
        await page.getByRole('button', { name: 'Yes, set as reached' }).click();
        await expect(page.getByText('Goal marked as reached.')).toBeVisible();
        await expect(page.getByText('This goal is already reached and can no longer be changed.')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Set as reached' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Add saved amount' })).toBeHidden();
        await expect(page.getByRole('link', { name: 'Edit' })).toBeHidden();

        await expect(page.getByRole('button', { name: 'Delete' })).toBeVisible();

        await page.goto('/goals');
        const reachedRow = page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) });
        await expect(reachedRow.locator('select')).toHaveValue('reached');
        await expect(reachedRow.locator('select')).toBeDisabled();

        await reachedRow.getByRole('link', { name: 'Edit' }).click();
        await expect(page.getByText('This goal is already reached and can no longer be changed.')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Save' })).toBeDisabled();

        await page.goto('/goals');
        await page.getByRole('row', { name: new RegExp(`${mainValue} Edited`) }).getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this goal?')).toBeVisible();
        await page.locator('#confirm-delete-goal').click();

        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('deletes a goal from its detail page', async ({ page }) => {
        const { email, password } = seedUser();
        const mainValue = `E2E Del ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/goals/create');
        await page.getByLabel('Name', { exact: true }).fill(mainValue);
        await page.getByLabel('Target Amount', { exact: true }).fill('1000000');
        await page.getByLabel('Desired Date', { exact: true }).fill('2027-03-15');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/goals');

        await page.getByRole('row', { name: new RegExp(mainValue) }).getByRole('link', { name: 'View' }).click();
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Are you sure you want to delete this goal?')).toBeVisible();
        await page.locator('#confirm-delete-goal').click();

        await expect(page).toHaveURL('/goals');
        await expect(page.getByText('Goal deleted successfully.')).toBeVisible();
        await expect(page.getByRole('row', { name: new RegExp(mainValue) })).toHaveCount(0);
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/goals');

        await expect(page).toHaveURL(/\/login$/);
    });

    test('redirects a guest from the goal detail page to login', async ({ page }) => {
        await page.goto('/goals/1');

        await expect(page).toHaveURL(/\/login$/);
    });
});
