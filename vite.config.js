import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],

    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,

        // Allow loading Vite assets from app/admin subdomains
        cors: {
            origin: [
                /^http:\/\/.*\.127\.0\.0\.1\.nip\.io:8000$/,
                'http://127.0.0.1:8000',
                'http://localhost:8000',
            ],
            credentials: true,
        },
        // Ensure HMR works when you're on admin.127.0.0.1.nip.io or app.127.0.0.1.nip.io
        hmr: {
            host: '127.0.0.1.nip.io',
            protocol: 'ws',
            port: 5173,
        },
    },
});