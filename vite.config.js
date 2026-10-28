import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import postcssOklabFunction from '@csstools/postcss-oklab-function';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    css: {
        postcss: {
            plugins: [
                // Firefox < 113 (e.g. Firefox 106 on Windows 7) does not
                // understand oklch()/oklab(). Emit an sRGB fallback while
                // preserving the modern value for current browsers.
                postcssOklabFunction({
                    preserve: true,
                }),
            ],
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,

        hmr: {
            host: '192.168.12.117',
            port: 5173,
        },

        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
