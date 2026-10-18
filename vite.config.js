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
        // Docker Desktop on WSL does not always forward file events from the bind mount,
        // so new Blade files were missed by Tailwind until a restart. Polling is reliable.
        watch: {
            usePolling: true,
            interval: 500,
            ignored: ['**/storage/framework/views/**', '**/vendor/**', '**/legacy/**'],
        },
    },
});
