import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'],
            refresh: true,
        }),
    ],
    build: {
        // Modern evergreen browsers: keep native CSS nesting, :has(), color-mix()…
        cssTarget: ['chrome120', 'edge120', 'firefox121', 'safari17.2'],
        target: 'es2022',
    },
    server: {
        host: '0.0.0.0',
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/public/uploads/**'],
            usePolling: true,
        },
    },
});
