import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        // Nos testes (Vitest) o plugin do Laravel deixa a URL dos assets importados como "undefined/...".
        !process.env.VITEST && laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/estudos.css',
                'resources/js/estudos/main.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    test: {
        include: ['tests/js/**/*.test.js'],
    },
});
