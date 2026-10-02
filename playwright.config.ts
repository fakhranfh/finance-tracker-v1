import { defineConfig, devices } from '@playwright/test';
import { testEnv } from './tests/playwright/support/env';

/**
 * Playwright config. Points at tests/playwright, per .ai/rules/e2e-testing.md.
 * Runs its own server on :8001 against the test DB (see support/env.ts),
 * so it never collides with the dev server on :8000.
 */
export default defineConfig({
    globalSetup: './tests/playwright/support/global-setup.ts',
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8001 --no-reload',
        url: 'http://127.0.0.1:8001/login',
        reuseExistingServer: false,
        env: testEnv as Record<string, string>,
    },
    testDir: './tests/playwright',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8001',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
