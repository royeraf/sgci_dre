import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    // Base fija: los chunks dinámicos se resuelven contra el mismo host.
    // Sin esto, laravel-vite-plugin usa ASSET_URL y "hornea" un dominio
    // absoluto en el bundle, lo que rompe con CORS si la app responde por
    // más de un dominio (digital y documentos).
    base: '/build/',
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
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rollupOptions: {
            output: {
                // El hosting de producción no sirve .mjs con el content-type
                // correcto (nginx no lo mapea a application/javascript), lo
                // que hace que el navegador rechace el worker de pdf.js por
                // MIME type inválido. Forzamos .js para todos los assets.
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name ?? assetInfo.names?.[0] ?? '';
                    if (name.endsWith('.mjs')) {
                        return 'assets/[name]-[hash].js';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});

