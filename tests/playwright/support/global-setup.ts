import { execSync } from 'node:child_process';
import { testEnv } from './env';

/**
 * Fresh schema on the dedicated test database before the suite runs, so e2e
 * never touches the dev database (mirrors Pest's RefreshDatabase).
 */
export default function globalSetup(): void {
    execSync('php artisan migrate:fresh --force', { stdio: 'inherit', env: testEnv });
}
