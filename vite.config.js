import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'BSM-POS',
                short_name: 'BSM-POS',
                lang: 'es-DO',
                theme_color: '#3525cd',
                background_color: '#fcf8ff',
                display: 'standalone',
            },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
