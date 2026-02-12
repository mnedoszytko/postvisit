import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { execSync } from 'child_process';

const gitHash = execSync('git rev-parse --short HEAD').toString().trim();
const buildTime = new Date().toISOString();

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    define: {
        __GIT_HASH__: JSON.stringify(gitHash),
        __BUILD_TIME__: JSON.stringify(buildTime),
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
