import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/driver/theme.css',
                'resources/js/app.js',
                'resources/js/capacitor-location.js'
            ],
            refresh: true,
        }),
    ],
});
