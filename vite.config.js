import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'],
            refresh: true,
            fonts: [
                // Body text in both languages. Preloading is off because browsers fetch
                // only the subsets and weights a page actually renders.
                bunny('IBM Plex Sans Arabic', {
                    alias: 'body',
                    weights: [400, 500, 600],
                    subsets: ['latin', 'arabic'],
                    preload: false,
                }),
                // Latin headings and the wordmark.
                bunny('Fraunces', {
                    alias: 'display-latin',
                    weights: [600],
                    preload: false,
                }),
                // Arabic headings.
                bunny('Noto Kufi Arabic', {
                    alias: 'display-arabic',
                    weights: [600],
                    subsets: ['arabic'],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
