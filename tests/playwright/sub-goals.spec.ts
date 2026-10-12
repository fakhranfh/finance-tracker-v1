import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

type SubGoalInput = { name: string; target: string; saved?: string };

/** Fills the goal form (a new goal, with sub goal rows) but does not submit it. */
async function fillGoalForm(page, name: string, target: string, subGoals: SubGoalInput[]) {
    await page.goto('/goals/create');
    await page.locator('input[name="name"]').fill(name);
    await page.locator('input[name="target_amount"]').fill(target);
    await page.locator('input[name="desired_date"]').fill('2027-03-15');

    for (const [index, subGoal] of subGoals.entries()) {
        await page.getByRole('button', { name: 'Add sub goal' }).click();
        await page.locator(`[name="sub_goals.${index}.name"]`).fill(subGoal.name);
        await page.locator(`[name="sub_goals.${index}.target_amount"]`).fill(subGoal.target);
        await page.locator(`[name="sub_goals.${index}.saved_amount"]`).fill(subGoal.saved ?? '0');
    }
}

/** Creates a goal with sub goals through the form and returns its id. */
async function createGoal(page, name: string, target: string, subGoals: SubGoalInput[]): Promise<string> {
    await fillGoalForm(page, name, target, subGoals);
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page).toHaveURL('/goals');

    const href = await page.getByRole('row', { name: new RegExp(name) }).getByRole('link', { name: 'View' }).getAttribute('href');

    return (href ?? '').split('/').pop() ?? '';
}

test.describe('Sub goals', () => {
    test('creates a goal with sub goals whose saved amounts become the goal saved amount', async ({ page }) => {
        const { email, password } = seedUser();
        const goalName = `E2E Wedding ${Date.now()}`;

        await login(page, email, password);
        await fillGoalForm(page, goalName, '10000000', [
            { name: 'Mas kawin', target: '6000000', saved: '1000000' },
            { name: 'Vendor katering', target: '4000000', saved: '500000' },
        ]);

        await expect(page.locator('input[name="saved_amount"]')).toHaveValue('1.500.000');
        await expect(page.locator('input[name="saved_amount"]')).toHaveJSProperty('readOnly', true);
        await expect(page.locator('[data-sub-goals-totals]')).toContainText('10.000.000');

        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/goals');

        await page.getByRole('row', { name: new RegExp(goalName) }).getByRole('link', { name: 'View' }).click();
        await expect(page.getByText('0 of 2 sub goals done')).toBeVisible();

        await page.getByRole('link', { name: 'Manage sub goals' }).click();
        await expect(page.getByRole('heading', { name: 'Sub goals' })).toBeVisible();
        const row = page.getByRole('row', { name: /Mas kawin/ });
        await expect(row).toContainText('6.000.000');
        await expect(row).toContainText('1.000.000');
        await expect(row).toContainText('Not done');

        await row.getByLabel('Mas kawin').check();
        await expect(row).toContainText('Done');
        await expect(page.locator('[data-sub-goals-summary]')).toContainText('1 of 2 sub goals done');
        await page.reload();
        await expect(page.getByRole('row', { name: /Mas kawin/ }).getByLabel('Mas kawin')).toBeChecked();
    });

    test('blocks sub goals that do not add up to the goal target', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await fillGoalForm(page, `E2E Mismatch ${Date.now()}`, '10000000', [{ name: 'Only part', target: '9000000' }]);

        await expect(page.locator('[data-sub-goals-totals]')).toHaveClass(/text-error/);
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/goals/create');
        await expect(page.locator('[data-error-for="sub_goals"]')).toContainText('must equal the goal target amount');
    });

    test('shows validation errors on a sub goal row', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await fillGoalForm(page, `E2E Row ${Date.now()}`, '10000000', []);
        await page.getByRole('button', { name: 'Add sub goal' }).click();
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/goals/create');
        await expect(page.locator('[data-error-for="sub_goals.0.name"]')).toBeVisible();
    });

    test('links savings to a sub goal, which raises its saved amount', async ({ page }) => {
        const { email, password } = seedUser();
        const goalName = `E2E Link ${Date.now()}`;

        await login(page, email, password);
        const goalId = await createGoal(page, goalName, '10000000', [
            { name: 'Mas kawin', target: '6000000' },
            { name: 'Vendor katering', target: '4000000' },
        ]);

        await page.goto(`/goals/${goalId}`);
        await expect(page.getByRole('columnheader', { name: 'Sub goal' })).toBeVisible();

        await page.getByRole('button', { name: 'Add saved amount' }).click();
        await page.getByLabel('Amount to add').fill('250000');
        await page.locator('#add-saved-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('#add-saved-form [data-error-for="sub_goal_id"]')).toBeVisible();

        await page.locator('#amount_sub_goal').selectOption({ label: 'Vendor katering' });
        await page.locator('#add-saved-form').getByRole('button', { name: 'Save' }).click();

        const history = page.locator('#goal-savings-table tr:not([data-table-empty-row]):not([data-table-bulk-row])');
        await expect(history).toHaveCount(1);
        await expect(history.first()).toContainText('Vendor katering');

        await page.goto(`/goals/${goalId}/sub-goals`);
        await expect(page.getByRole('row', { name: /Vendor katering/ })).toContainText('250.000');
    });

    test('edits sub goals together with the goal', async ({ page }) => {
        const { email, password } = seedUser();
        const goalName = `E2E Edit ${Date.now()}`;

        await login(page, email, password);
        const goalId = await createGoal(page, goalName, '10000000', [
            { name: 'Mas kawin', target: '6000000', saved: '1000000' },
            { name: 'Vendor katering', target: '4000000' },
        ]);

        await page.goto(`/goals/${goalId}/edit`);
        await expect(page.locator('[name="sub_goals.0.name"]')).toHaveValue('Mas kawin');
        await expect(page.locator('[name="sub_goals.0.saved_amount"]')).toHaveValue('1.000.000');
        await expect(page.locator('[name="sub_goals.0.saved_amount"]')).toHaveJSProperty('readOnly', true);

        await page.locator('[name="sub_goals.1.name"]').fill('Katering baru');
        await page.locator('[name="sub_goals.1.target_amount"]').fill('3000000');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(`/goals/${goalId}/edit`);
        await expect(page.locator('[data-error-for="sub_goals"]')).toContainText('must equal the goal target amount');

        await page.locator('[name="sub_goals.1.target_amount"]').fill('4000000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/goals');

        await page.goto(`/goals/${goalId}/sub-goals`);
        await expect(page.getByRole('row', { name: /Katering baru/ })).toBeVisible();
    });

    test('edits the sub goals of a goal on their own page', async ({ page }) => {
        const { email, password } = seedUser();
        const goalName = `E2E Own ${Date.now()}`;

        await login(page, email, password);
        const goalId = await createGoal(page, goalName, '10000000', [
            { name: 'Mas kawin', target: '6000000', saved: '1000000' },
            { name: 'Vendor katering', target: '4000000' },
        ]);

        await page.goto(`/goals/${goalId}/sub-goals`);
        await page.getByRole('link', { name: 'Edit sub goals' }).click();
        await expect(page).toHaveURL(`/goals/${goalId}/sub-goals/edit`);
        await expect(page.getByRole('heading', { name: 'Edit sub goals' })).toBeVisible();
        await expect(page.getByText(goalName)).toBeVisible();
        await expect(page.locator('[name="sub_goals.0.name"]')).toHaveValue('Mas kawin');

        await page.locator('[name="sub_goals.1.target_amount"]').fill('3000000');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL(`/goals/${goalId}/sub-goals/edit`);
        await expect(page.locator('[data-error-for="sub_goals"]')).toContainText('must equal the goal target amount');

        await page.locator('[name="sub_goals.1.name"]').fill('Katering baru');
        await page.locator('[name="sub_goals.1.target_amount"]').fill('4000000');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(`/goals/${goalId}/sub-goals`);
        await expect(page.getByText('Sub goals saved successfully.')).toBeVisible();
        await expect(page.getByRole('row', { name: /Katering baru/ })).toBeVisible();
    });

    test('shows a page for each sub goal with its own saving history', async ({ page }) => {
        const { email, password } = seedUser();
        const goalName = `E2E Detail ${Date.now()}`;

        await login(page, email, password);
        const goalId = await createGoal(page, goalName, '10000000', [
            { name: 'Mas kawin', target: '6000000', saved: '1500000' },
            { name: 'Vendor katering', target: '4000000' },
        ]);

        await page.goto(`/goals/${goalId}`);
        await page.getByRole('button', { name: 'Add saved amount' }).click();
        await page.getByLabel('Amount to add').fill('250000');
        await page.locator('#amount_sub_goal').selectOption({ label: 'Vendor katering' });
        await page.locator('#add-saved-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Saved amount updated.')).toBeVisible();

        await page.goto(`/goals/${goalId}/sub-goals`);
        await page.getByRole('row', { name: /Vendor katering/ }).getByRole('link', { name: 'View' }).click();
        await expect(page).toHaveURL(new RegExp(`/goals/${goalId}/sub-goals/[0-9]+$`));
        await expect(page.getByRole('heading', { name: 'Sub goal details' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Vendor katering' })).toBeVisible();
        await expect(page.getByRole('link', { name: goalName })).toBeVisible();
        await expect(page.locator('[data-field="progress_label"]')).toHaveText('6%');

        const history = page.locator('#sub-goal-savings-table tr:not([data-table-empty-row]):not([data-table-bulk-row])');
        await expect(history).toHaveCount(1);
        await expect(history.first()).toContainText('250.000');

        await page.getByRole('button', { name: 'Mark as done' }).click();
        await expect(page.getByText('Sub goal marked as done.')).toBeVisible();
        await expect(page.locator('[data-field="status"]')).toHaveText('Done');
        await expect(page.getByRole('button', { name: 'Mark as not done' })).toBeVisible();

        await page.getByRole('link', { name: 'Edit sub goal' }).click();
        await expect(page).toHaveURL(new RegExp(`/goals/${goalId}/sub-goals/[0-9]+/edit$`));
    });

    test('edits a single sub goal on its own page without touching its amounts', async ({ page }) => {
        const { email, password } = seedUser();
        const goalName = `E2E Item ${Date.now()}`;

        await login(page, email, password);
        const goalId = await createGoal(page, goalName, '10000000', [
            { name: 'Mas kawin', target: '6000000', saved: '1000000' },
            { name: 'Vendor katering', target: '4000000' },
        ]);

        await page.goto(`/goals/${goalId}/sub-goals`);
        await page.getByRole('row', { name: /Mas kawin/ }).getByRole('link', { name: 'Edit' }).click();
        await expect(page).toHaveURL(new RegExp(`/goals/${goalId}/sub-goals/[0-9]+/edit$`));
        await expect(page.getByRole('heading', { name: 'Edit sub goal' })).toBeVisible();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue('Mas kawin');
        await expect(page.getByLabel('Target Amount', { exact: true })).toHaveValue('6.000.000');
        await expect(page.getByLabel('Target Amount', { exact: true })).toHaveJSProperty('readOnly', true);
        await expect(page.getByLabel('Saved Amount', { exact: true })).toHaveValue('1.000.000');

        await page.getByLabel('Name', { exact: true }).fill('');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('[data-error-for="name"]')).toBeVisible();

        await page.getByLabel('Name', { exact: true }).fill('Mas kawin emas');
        await page.getByLabel('Note', { exact: true }).fill('Dua puluh gram');
        await page.getByLabel('Done', { exact: true }).check();
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(new RegExp(`/goals/${goalId}/sub-goals/[0-9]+$`));
        await expect(page.getByText('Sub goal updated successfully.')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Mas kawin emas' })).toBeVisible();
        await expect(page.getByText('Dua puluh gram')).toBeVisible();
        await expect(page.locator('[data-field="status"]')).toHaveText('Done');
        await expect(page.locator('[data-field="target_amount"]')).toContainText('6.000.000');
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/goals/1/sub-goals');

        await expect(page).toHaveURL(/\/login$/);

        await page.goto('/goals/1/sub-goals/edit');

        await expect(page).toHaveURL(/\/login$/);

        await page.goto('/goals/1/sub-goals/1');

        await expect(page).toHaveURL(/\/login$/);
    });
});
