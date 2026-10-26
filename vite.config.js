import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/auth/login.js',
                'resources/js/auth/register.js',
                'resources/js/auth/forgot-password.js',
                'resources/js/auth/reset-password.js',
                'resources/js/auth/verify-email.js',
                'resources/js/dashboard.js',
                'resources/js/account-cards/index.js',
                'resources/js/profile/edit.js',
                'resources/js/accounts/index.js',
                'resources/js/accounts/form.js',
                'resources/js/accounts/summary.js',
                'resources/js/records/index.js',
                'resources/js/records/form.js',
                'resources/js/records/import.js',
                'resources/js/categories/index.js',
                'resources/js/categories/form.js',
                'resources/js/goals/index.js',
                'resources/js/goals/form.js',
                'resources/js/goals/show.js',
                'resources/js/sub-goals/index.js',
                'resources/js/sub-goals/form.js',
                'resources/js/sub-goals/show.js',
                'resources/js/sub-goals/item-form.js',
                'resources/js/webhooks/index.js',
                'resources/js/webhooks/form.js',
                'resources/js/webhooks/show.js',
                'resources/js/webhooks/test.js',
                'resources/js/webhooks/logs.js',
                'resources/js/webhooks/api-docs.js',
                'resources/js/debts/index.js',
                'resources/js/debts/form.js',
                'resources/js/receivables/index.js',
                'resources/js/receivables/form.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
