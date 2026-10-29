import { execSync } from 'node:child_process';
import { testEnv } from './env';

/**
 * CLI-based fixture seeding for E2E specs, via `php artisan test:seed-e2e-fixture`
 * (app/Console/Commands/SeedE2eFixture.php). Not an HTTP endpoint or a page
 * "Generate data" button — per .ai/rules/dev-autofill.md that dev-UI feature
 * is scoped to pages that display a table/list of data.
 */
function seed(kind: 'user' | 'admin' | 'reset-token'): Record<string, string> {
    const output = execSync(`php artisan test:seed-e2e-fixture ${kind}`, { env: testEnv }).toString().trim();

    return JSON.parse(output);
}

export function seedUser(): { email: string; password: string } {
    return seed('user') as { email: string; password: string };
}

export function seedAdmin(): { email: string; password: string } {
    return seed('admin') as { email: string; password: string };
}

export function seedResetToken(): { email: string; token: string } {
    return seed('reset-token') as { email: string; token: string };
}

/** Registration is only open while no users exist, so its specs start from an empty DB. */
export function resetDatabase(): void {
    execSync('php artisan migrate:fresh --force', { env: testEnv, stdio: 'ignore' });
}
