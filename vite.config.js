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
                'resources/js/setup-wizard.js',
                'resources/js/property-form.js',
                'resources/js/settings.js',
                'resources/js/password-input.js',
                'resources/js/favourites.js',
                'resources/js/staff.js',
                'resources/js/tenant-chrome.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // A build used to empty public/build/assets, so every hashed file a page already
        // open in someone's browser referred to stopped existing the moment we deployed.
        // The stylesheet 404s, the page renders with no CSS at all, and every icon lays
        // out at viewport size. Old files are left in place instead; they are hashed, so
        // nothing collides, and they can be pruned deliberately.
        emptyOutDir: false,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
