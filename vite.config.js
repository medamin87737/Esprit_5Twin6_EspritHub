import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    resolve: {
        alias: {
            jquery: fileURLToPath(new URL('./resources/assets/admin/vendor/jquery/jquery.min.js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/assets/front/css/styles.css',
                'resources/assets/front/css/nutritrace.css',
                'resources/assets/front/js/front.js',
                'resources/assets/admin/css/sb-admin-2.css',
                'resources/assets/admin/css/admin.css',
                'resources/assets/admin/js/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: 5180,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
