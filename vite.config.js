import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // Runs inside Docker (vite service); the browser reaches it on the published port.
    server: {
        host: '0.0.0.0',
        port: 5183,
        strictPort: true,
        origin: 'http://localhost:5183',
        cors: true,
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**', '**/legacy/**'],
        },
    },
});
