import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Loaded only by the handful of screens that need them, so the rest of the
                // site does not pay for a charting library it never draws with.
                'resources/js/chart.js',
                'resources/js/sortable.js',
                'resources/js/tenant-widgets.js',
                'resources/js/cookie-consent.js',
                'resources/js/flash.js',
                'resources/js/marketing.js',
                'resources/js/admin-chrome.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
