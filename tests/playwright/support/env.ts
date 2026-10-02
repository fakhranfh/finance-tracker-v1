/**
 * Env forced onto every PHP process Playwright spawns (server, seed commands,
 * migrations), using its own database, separate from Pest's (phpunit.xml) so both can run concurrently.
 */
export const testEnv = {
    ...process.env,
    APP_ENV: 'testing',
    DB_CONNECTION: 'pgsql',
    DB_DATABASE: process.env.DB_DATABASE_TEST ?? 'laravel_13_boilerplate_v1_e2e',
    DB_URL: '',
    BCRYPT_ROUNDS: '4',
    CACHE_STORE: 'array',
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
    PULSE_ENABLED: 'false',
    TELESCOPE_ENABLED: 'false',
    NIGHTWATCH_ENABLED: 'false',
};
