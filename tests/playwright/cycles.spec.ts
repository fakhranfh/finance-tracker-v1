import { test, expect } from '@playwright/test';
import { seedUser } from './support/seed';

async function login(page, email: string, password: string) {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL('/dashboard');
}

/** YYYY-MM-DD, `days` days before today (local time, like the date inputs). */
function isoDaysAgo(days: number): string {
    const date = new Date();

    date.setDate(date.getDate() - days);

    return [date.getFullYear(), date.getMonth() + 1, date.getDate()].map((part) => String(part).padStart(2, '0')).join('-');
}

test.describe('Usage Cycle', () => {
    test('reaches the page from the sidebar and shows its key elements (happy path)', async ({ page }) => {
        const { email, password } = seedUser();

        await login(page, email, password);
        await page.locator('aside').getByRole('link', { name: 'Usage Cycle' }).click();

        await expect(page).toHaveURL('/cycles');
        await expect(page.getByRole('heading', { name: 'Usage Cycle' })).toBeVisible();
        await expect(page.getByPlaceholder('Search...')).toBeVisible();
        await expect(page.getByText('Estimated monthly budget')).toBeVisible();
        await expect(page.locator('#cycles-add-category')).toBeVisible();
    });

    test('adds a category with an icon from the header and from the list', async ({ page }) => {
        const { email, password } = seedUser();
        const fromHeader = `E2E Header ${Date.now()}`;
        const fromList = `E2E List ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/cycles');
        await expect(page.locator('#cycles-content')).toBeVisible();

        // The header button opens the same modal as the "Add category" tile, with an icon to pick.
        await page.locator('#cycles-add-category').click();
        await expect(page.getByRole('dialog', { name: 'Add category' })).toBeVisible();
        await page.getByLabel('Name', { exact: true }).fill(fromHeader);
        await page.getByTitle('Gift').click();
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL('/cycles');
        await expect(page.getByText('Category added.')).toBeVisible();
        const headerTile = page.getByRole('link', { name: new RegExp(fromHeader) });
        await expect(headerTile).toBeVisible();
        await expect(headerTile.locator('svg')).toBeVisible();

        await page.locator('[data-add-tile]').click();
        await page.getByLabel('Name', { exact: true }).fill(fromList);
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByRole('link', { name: new RegExp(fromList) })).toBeVisible();

        // The category keeps the chosen icon on the Categories page.
        await page.goto('/categories');
        await page.getByPlaceholder(/Search/).first().fill(fromHeader);
        await expect(page.getByRole('row', { name: new RegExp(fromHeader) })).toBeVisible();
    });

    test('tracks purchases from category to product detail and works out the monthly budget', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Body Care ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(category);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/cycles');
        await page.getByRole('link', { name: new RegExp(category) }).click();
        await expect(page.getByRole('heading', { name: category })).toBeVisible();

        // An item name inside the category.
        await page.getByRole('button', { name: 'Add item name' }).click();
        await page.getByLabel('Name', { exact: true }).fill('Sabun Mandi');
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Item name added.')).toBeVisible();
        await page.getByRole('link', { name: /Sabun Mandi/ }).click();
        await expect(page.getByRole('heading', { name: 'Sabun Mandi' })).toBeVisible();

        // First purchase, bought 40 days ago and finished 12 days ago (it lasted 28 days).
        await page.getByRole('button', { name: 'Add purchase' }).click();
        await page.getByLabel('Product / brand').fill('Shinzui');
        await page.getByLabel('Price', { exact: true }).fill('35000');
        await page.getByLabel('Date bought').fill(isoDaysAgo(40));
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Purchase added.')).toBeVisible();
        await expect(page.getByRole('link', { name: /Shinzui/ })).toBeVisible();

        await page.getByRole('button', { name: 'Mark finished' }).click();
        await page.getByLabel('Date finished').fill(isoDaysAgo(12));
        await page.locator('#cycle-finish-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Marked as finished.')).toBeVisible();
        await expect(page.locator('[data-summary="monthly"]')).toContainText('37.500');

        // Second purchase starts a new cycle with an estimated run-out date.
        await page.getByRole('button', { name: 'Add purchase' }).click();
        await expect(page.getByLabel('Product / brand')).toHaveValue('Shinzui');
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByRole('link', { name: /Shinzui/ })).toHaveCount(2);
        // Only the purchase still in use has its own check button; the finished one has none.
        await expect(page.getByRole('button', { name: 'Mark finished' })).toHaveCount(1);

        await page.getByRole('link', { name: /Shinzui/ }).last().click();
        await expect(page.getByRole('heading', { name: 'Product Detail' })).toBeVisible();
        await expect(page.getByText('Estimated to run out:')).toBeVisible();
        await expect(page.getByText('Price: Rp', { exact: false })).toContainText('35.000');

        // Edit from the menu.
        await page.getByRole('button', { name: 'More actions' }).click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();
        await page.getByLabel('Price', { exact: true }).fill('40000');
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Purchase updated.')).toBeVisible();
        await expect(page.getByText('Price: Rp', { exact: false })).toContainText('40.000');

        // Delete it again, which returns to the timeline.
        await page.getByRole('button', { name: 'More actions' }).click();
        await page.getByRole('menuitem', { name: 'Delete' }).click();
        await page.locator('#confirm-delete-cycle').click();
        await expect(page).toHaveURL(/\/cycles\/\d+\/\d+$/);
        await expect(page.getByText('Purchase deleted.')).toBeVisible();
        await expect(page.getByRole('link', { name: /Shinzui/ })).toHaveCount(1);
    });

    test('shows the monthly cost on the category and item name tiles', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Skin Care ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(category);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/cycles');
        await page.getByRole('link', { name: new RegExp(category) }).click();
        await page.getByRole('button', { name: 'Add item name' }).click();
        await page.getByLabel('Name', { exact: true }).fill('Serum');
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await page.getByRole('link', { name: /Serum/ }).click();
        await expect(page.getByRole('heading', { name: 'Serum' })).toBeVisible();

        await page.getByRole('button', { name: 'Add purchase' }).click();
        await page.getByLabel('Product / brand').fill('Somethinc Niacinamide');
        await page.getByLabel('Price', { exact: true }).fill('90000');
        await page.getByLabel('Date bought').fill(isoDaysAgo(60));
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();
        await page.getByRole('button', { name: 'Mark finished' }).click();
        await page.getByLabel('Date finished').fill(isoDaysAgo(30));
        await page.locator('#cycle-finish-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Marked as finished.')).toBeVisible();

        // 90.000 / 30 days * 30 = 90.000 per month, on the item name tile and on the category tile.
        await page.getByRole('link', { name: 'Back' }).click();
        await expect(page.getByRole('link', { name: /Serum/ })).toContainText('90.000 / month');
        await page.getByRole('link', { name: 'Back' }).click();
        await expect(page.getByRole('link', { name: new RegExp(category) })).toContainText('90.000 / month');
    });

    test('rejects a finish date before the purchase date', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Dates ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(category);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/cycles');
        await page.getByRole('link', { name: new RegExp(category) }).click();
        await page.getByRole('button', { name: 'Add item name' }).click();
        await page.getByLabel('Name', { exact: true }).fill('Toner');
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await page.getByRole('link', { name: /Toner/ }).click();
        await expect(page.getByRole('heading', { name: 'Toner' })).toBeVisible();

        await page.getByRole('button', { name: 'Add purchase' }).click();
        await page.getByLabel('Product / brand').fill('Avoskin Miraculous Toner');
        await page.getByLabel('Price', { exact: true }).fill('125000');
        await page.getByLabel('Date bought').fill(isoDaysAgo(5));
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();

        await page.getByRole('button', { name: 'Mark finished' }).click();
        await page.getByLabel('Date finished').fill(isoDaysAgo(10));
        await page.locator('#cycle-finish-form').getByRole('button', { name: 'Save' }).click();

        await expect(page.getByText('The finish date cannot be before the purchase date.')).toBeVisible();
    });

    test('pages a long purchase timeline and lets the page size change', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Paging ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(category);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/cycles');
        await page.getByRole('link', { name: new RegExp(category) }).click();
        await page.getByRole('button', { name: 'Add item name' }).click();
        await page.getByLabel('Name', { exact: true }).fill('Lotion');
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await page.getByRole('link', { name: /Lotion/ }).click();
        await expect(page.getByRole('heading', { name: 'Lotion' })).toBeVisible();

        // 12 purchases in total: one through the form, eleven through the API.
        await page.getByRole('button', { name: 'Add purchase' }).click();
        await page.getByLabel('Product / brand').fill('Vaseline Healthy White');
        await page.getByLabel('Price', { exact: true }).fill('42000');
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Purchase added.')).toBeVisible();

        const typeId = page.url().split('/').pop();

        await page.evaluate(async (id) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            for (let day = 1; day <= 11; day += 1) {
                await fetch(`/api/cycle-types/${id}/entries`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ name: `Nivea Body Lotion ${day}`, price: 40000, bought_at: `2026-01-${String(day).padStart(2, '0')}` }),
                });
            }
        }, typeId);
        await page.reload();

        await expect(page.locator('[data-entry]')).toHaveCount(10);
        // The page buttons show above and below the list.
        await expect(page.getByRole('button', { name: '2', exact: true })).toHaveCount(2);
        // Changing page asks the API for that page.
        const pageTwo = page.waitForResponse((response) => response.url().includes(`/api/cycle-types/${typeId}/entries`) && response.url().includes('page=2'));
        await page.getByRole('button', { name: '2', exact: true }).first().click();
        expect((await pageTwo).ok()).toBe(true);
        await expect(page.locator('[data-entry]')).toHaveCount(2);
        await page.getByRole('button', { name: '1', exact: true }).last().click();
        await expect(page.locator('[data-entry]')).toHaveCount(10);
        await page.getByRole('button', { name: '2', exact: true }).last().click();
        await expect(page.locator('[data-entry]')).toHaveCount(2);

        // Items per page shows above and below the list too, and both stay in step.
        await expect(page.locator('[data-per-page-select]')).toHaveCount(2);
        await page.locator('[data-per-page-select]').first().selectOption('25');
        await expect(page.locator('[data-per-page-select]').last()).toHaveValue('25');
        await expect(page.locator('[data-entry]')).toHaveCount(12);
        await expect(page.getByRole('button', { name: '2', exact: true })).toHaveCount(0);

        // Searching starts again from the first page of the matches.
        const searched = page.waitForResponse((response) => response.url().includes(`/api/cycle-types/${typeId}/entries`) && response.url().includes('search=Vaseline'));
        await page.getByPlaceholder('Search...').fill('Vaseline');
        expect((await searched).ok()).toBe(true);
        await expect(page.locator('[data-entry]')).toHaveCount(1);
    });

    test('pages and searches the categories through the API', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();

        await login(page, email, password);
        await page.goto('/cycles');
        await expect(page.getByRole('heading', { name: 'Usage Cycle' })).toBeVisible();

        await page.evaluate(async (prefix) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            for (let number = 1; number <= 13; number += 1) {
                await fetch('/api/categories', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ name: `${prefix} ${String(number).padStart(2, '0')}`, type: 'expense', color: '#2563EB', icon: 'tag' }),
                });
            }
        }, `E2E Cat ${stamp}`);
        await page.reload();

        // 13 categories at 12 per page: 12 tiles on the first page, the last one (and "Add category") on the second.
        await expect(page.locator('[data-tile]')).toHaveCount(12);
        await expect(page.locator('[data-add-tile]')).toHaveCount(0);

        const pageTwo = page.waitForResponse((response) => response.url().includes('/api/cycle-categories') && response.url().includes('page=2'));
        await page.getByRole('button', { name: '2', exact: true }).first().click();
        expect((await pageTwo).ok()).toBe(true);
        await expect(page.locator('[data-tile]')).toHaveCount(1);
        await expect(page.locator('[data-add-tile]')).toHaveCount(1);

        // Searching asks the API and starts again from the first page.
        const searched = page.waitForResponse((response) => response.url().includes('/api/cycle-categories') && response.url().includes('search='));
        await page.getByPlaceholder('Search...').fill(`${stamp} 07`);
        expect((await searched).ok()).toBe(true);
        await expect(page.locator('[data-tile]')).toHaveCount(1);
        await expect(page.locator('[data-tile]')).toContainText(`${stamp} 07`);
        await expect(page.getByRole('button', { name: '2', exact: true })).toHaveCount(0);

        await page.getByPlaceholder('Search...').fill(`${stamp} nothing`);
        await expect(page.getByText('Nothing matches your search.')).toBeVisible();
    });

    test('edits, finishes, deletes and bulk deletes purchases from the timeline', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Manage ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(category);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/cycles');
        await page.getByRole('link', { name: new RegExp(category) }).click();
        await page.getByRole('button', { name: 'Add item name' }).click();
        await page.getByLabel('Name', { exact: true }).fill('Deodoran');
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await page.getByRole('link', { name: /Deodoran/ }).click();
        await expect(page.getByRole('heading', { name: 'Deodoran' })).toBeVisible();

        const typeId = page.url().split('/').pop();
        const dates = [isoDaysAgo(10), isoDaysAgo(9), isoDaysAgo(8), isoDaysAgo(7)];

        await page.evaluate(
            async ([id, boughtOn]) => {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                for (const [index, name] of ['Alpha', 'Bravo', 'Charlie', 'Delta'].entries()) {
                    await fetch(`/api/cycle-types/${id}/entries`, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ name, price: 28000, bought_at: boughtOn[index] }),
                    });
                }
            },
            [typeId, dates] as const,
        );
        await page.reload();

        const row = (name: string) => page.locator('li', { hasText: name });

        await expect(page.locator('[data-entry]')).toHaveCount(4);

        // Edit one.
        await row('Bravo').getByRole('button', { name: 'Edit' }).click();
        await page.getByLabel('Product / brand').fill('Bravo Edited');
        await page.locator('#cycle-entry-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Purchase updated.')).toBeVisible();
        await expect(row('Bravo Edited')).toBeVisible();

        // Finish one: its own check button goes away.
        await row('Charlie').getByRole('button', { name: 'Mark finished' }).click();
        await page.locator('#cycle-finish-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Marked as finished.')).toBeVisible();
        await expect(row('Charlie').getByRole('button', { name: 'Mark finished' })).toHaveCount(0);

        // Delete one.
        await row('Alpha').getByRole('button', { name: 'Delete' }).click();
        await page.locator('#confirm-delete-cycle').click();
        await expect(page.getByText('Purchase deleted.')).toBeVisible();
        await expect(page.locator('[data-entry]')).toHaveCount(3);

        // Bulk delete two ticked purchases.
        await page.getByLabel('Bravo Edited', { exact: true }).check();
        await page.getByLabel('Charlie', { exact: true }).check();
        await expect(page.getByText('2 selected')).toBeVisible();
        await page.getByRole('button', { name: 'Delete selected' }).click();
        await page.locator('#confirm-bulk-delete-cycle').click();
        await expect(page.getByText('Selected purchases deleted.')).toBeVisible();
        await expect(page.locator('[data-entry]')).toHaveCount(1);
        await expect(row('Delta')).toBeVisible();

        // Select all on the page, then delete the rest.
        await page.locator('#cycles-select-all').check();
        await expect(page.getByText('1 selected')).toBeVisible();
        await page.getByRole('button', { name: 'Delete selected' }).click();
        await page.locator('#confirm-bulk-delete-cycle').click();
        await expect(page.getByText('No purchases logged yet.')).toBeVisible();
    });

    test('pages and searches the item names through the API', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Types ${Date.now()}`;

        await login(page, email, password);

        await page.goto('/categories/create');
        await page.getByLabel('Name', { exact: true }).fill(category);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page).toHaveURL('/categories');

        await page.goto('/cycles');
        await page.getByRole('link', { name: new RegExp(category) }).click();
        await expect(page.getByRole('heading', { name: category })).toBeVisible();
        await expect(page.locator('#cycles-content')).toBeVisible();

        const categoryId = page.url().split('/').pop();

        await page.evaluate(async (id) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            for (let number = 1; number <= 13; number += 1) {
                await fetch(`/api/cycle-categories/${id}/types`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ name: `Type ${String(number).padStart(2, '0')}` }),
                });
            }
        }, categoryId);
        await page.reload();

        await expect(page.locator('[data-tile]')).toHaveCount(12);

        const pageTwo = page.waitForResponse((response) => response.url().includes(`/api/cycle-categories/${categoryId}/types`) && response.url().includes('page=2'));
        await page.getByRole('button', { name: '2', exact: true }).last().click();
        expect((await pageTwo).ok()).toBe(true);
        await expect(page.locator('[data-tile]')).toHaveCount(1);
        await expect(page.locator('[data-add-tile]')).toHaveCount(1);

        const searched = page.waitForResponse((response) => response.url().includes(`/api/cycle-categories/${categoryId}/types`) && response.url().includes('search=07'));
        await page.getByPlaceholder('Search...').fill('07');
        expect((await searched).ok()).toBe(true);
        await expect(page.locator('[data-tile]')).toHaveCount(1);
        await expect(page.locator('[data-tile]')).toContainText('Type 07');
    });

    test('edits, deletes and bulk deletes categories', async ({ page }) => {
        const { email, password } = seedUser();
        const stamp = Date.now();

        await login(page, email, password);
        await page.goto('/cycles');
        await expect(page.locator('#cycles-content')).toBeVisible();

        await page.evaluate(async (prefix) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            for (const name of ['Alpha', 'Bravo', 'Charlie']) {
                await fetch('/api/categories', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ name: `${prefix} ${name}`, type: 'expense', color: '#2563EB', icon: 'tag' }),
                });
            }
        }, `E2E ${stamp}`);
        await page.reload();

        const card = (name: string) => page.locator('[data-id]', { hasText: name });

        await expect(page.locator('[data-tile]')).toHaveCount(3);

        // Edit one: rename it and pick another icon.
        await card('Bravo').getByRole('button', { name: /^Edit/ }).click();
        await expect(page.getByRole('dialog', { name: 'Edit category' })).toBeVisible();
        await expect(page.getByLabel('Name', { exact: true })).toHaveValue(`E2E ${stamp} Bravo`);
        await page.getByLabel('Name', { exact: true }).fill(`E2E ${stamp} Bravo Edited`);
        await page.getByTitle('Gift').click();
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Category updated.')).toBeVisible();
        await expect(card('Bravo Edited')).toBeVisible();

        // Delete one.
        await card('Alpha').getByRole('button', { name: /^Delete/ }).click();
        await expect(page.getByRole('dialog', { name: 'Delete this category?' })).toBeVisible();
        await page.locator('#confirm-delete-tile').click();
        await expect(page.getByText('Category deleted.')).toBeVisible();
        await expect(page.locator('[data-tile]')).toHaveCount(2);

        // Bulk delete the rest.
        await page.getByLabel(`E2E ${stamp} Bravo Edited`, { exact: true }).check();
        await page.getByLabel(`E2E ${stamp} Charlie`, { exact: true }).check();
        await expect(page.getByText('2 selected')).toBeVisible();
        await page.getByRole('button', { name: 'Delete selected' }).click();
        await page.locator('#confirm-bulk-delete-tiles').click();
        await expect(page.getByText('Selected categories deleted.')).toBeVisible();
        await expect(page.locator('[data-tile]')).toHaveCount(0);
        await expect(page.getByText('No categories yet.')).toBeVisible();
    });

    test('edits, deletes and bulk deletes item names', async ({ page }) => {
        const { email, password } = seedUser();
        const category = `E2E Names ${Date.now()}`;

        await login(page, email, password);
        await page.goto('/cycles');
        await expect(page.locator('#cycles-content')).toBeVisible();

        const categoryId = await page.evaluate(async (name) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token };
            const created = await fetch('/api/categories', { method: 'POST', headers, body: JSON.stringify({ name, type: 'expense', color: '#2563EB', icon: 'tag' }) });
            const id = (await created.json()).data.id;

            for (const type of ['Serum', 'Toner', 'Lotion']) {
                await fetch(`/api/cycle-categories/${id}/types`, { method: 'POST', headers, body: JSON.stringify({ name: type }) });
            }

            return id;
        }, category);

        await page.goto(`/cycles/${categoryId}`);
        await expect(page.getByRole('heading', { name: category })).toBeVisible();
        await expect(page.locator('[data-tile]')).toHaveCount(3);

        const card = (name: string) => page.locator('[data-id]', { hasText: name });

        // Edit one.
        await card('Toner').getByRole('button', { name: /^Edit/ }).click();
        await expect(page.getByRole('dialog', { name: 'Edit item name' })).toBeVisible();
        await page.getByLabel('Name', { exact: true }).fill('Toner Pro');
        await page.getByTitle('Sun').click();
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText('Item name updated.')).toBeVisible();
        await expect(card('Toner Pro')).toBeVisible();

        // A name already used in the category is rejected where it is typed.
        await card('Serum').getByRole('button', { name: /^Edit/ }).click();
        await page.getByLabel('Name', { exact: true }).fill('Lotion');
        await page.locator('#cycle-tile-form').getByRole('button', { name: 'Save' }).click();
        await expect(page.getByText(/already been taken/)).toBeVisible();
        await page.getByRole('button', { name: 'Cancel' }).click();

        // Delete one.
        await card('Serum').getByRole('button', { name: /^Delete/ }).click();
        await expect(page.getByRole('dialog', { name: 'Delete this item name?' })).toBeVisible();
        await page.locator('#confirm-delete-tile').click();
        await expect(page.getByText('Item name deleted.')).toBeVisible();
        await expect(page.locator('[data-tile]')).toHaveCount(2);

        // Select all on the page, then bulk delete.
        await page.locator('#cycles-select-all').check();
        await expect(page.getByText('2 selected')).toBeVisible();
        await page.getByRole('button', { name: 'Delete selected' }).click();
        await page.locator('#confirm-bulk-delete-tiles').click();
        await expect(page.getByText('Selected item names deleted.')).toBeVisible();
        await expect(page.locator('[data-tile]')).toHaveCount(0);
        await expect(page.getByText('No item names in this category yet.')).toBeVisible();
    });

    test('redirects a guest to login', async ({ page }) => {
        await page.goto('/cycles');

        await expect(page).toHaveURL(/\/login$/);
    });
});
