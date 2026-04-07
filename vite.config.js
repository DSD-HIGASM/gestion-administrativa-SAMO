import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            valetTls: 'gestion-administrativa-samo.test'
        }),
    ],
    server: {
        host: 'gestion-administrativa-samo.test',
        hmr: {
            host: 'gestion-administrativa-samo.test'
        }
    }
});
