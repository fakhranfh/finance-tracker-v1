import { execSync } from 'node:child_process';
import { testEnv } from './env';

/**
 * CLI-based fixture seeding for E2E specs, via `php artisan test:seed-e2e-fixture`
 * (app/Console/Commands/SeedE2eFixture.php). Not an HTTP endpoint or a page
 * "Generate data" button — per .ai/rules/dev-autofill.md that dev-UI feature
 * is scoped to pages that display a table/list of data.
 */
function seed(kind: 'user' | 'reset-token'): Record<string, string> {
    const output = execSync(`php artisan test:seed-e2e-fixture ${kind}`, { env: testEnv }).toString().trim();

    return JSON.parse(output);
}

export function seedUser(): { email: string; password: string } {
    return seed('user') as { email: string; password: string };
}

export function seedResetToken(): { email: string; token: string } {
    return seed('reset-token') as { email: string; token: string };
}
