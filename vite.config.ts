import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import { resolve } from 'path';

export default defineConfig({
    base: './',
    plugins: [
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
   build: {
        outDir: 'public/build',
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                severite: resolve(__dirname, 'resources/js/app.ts'),
            },
            output: {
                assetFileNames: 'assets/severite/[name]-[hash][extname]',
                chunkFileNames: 'assets/severite/[name]-[hash].js',
                entryFileNames: 'assets/severite/[name]-[hash].js',
            },
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js/', import.meta.url)),
        },
    },
});
