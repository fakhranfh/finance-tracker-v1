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
                'resources/js/dashboard.js',
                'resources/js/users/index.js',
                'resources/js/users/form.js',
                'resources/js/profile/edit.js',
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
