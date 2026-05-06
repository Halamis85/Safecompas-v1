import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/login.css',
                'resources/js/app.js',
                'resources/js/login.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],

    build: {
        cssCodeSplit: true,
        sourcemap: false,
        target: 'es2020',
        minify: 'esbuild',

        rollupOptions: {
            output: {
                /**
                 * Vendor chunky se cachují mezi deployi.
                 * Po Fázi 2A už neexistují pdfmake/jszip/jquery — z těchto
                 * chunků zbyly jen reálně používané knihovny.
                 */
                manualChunks(id) {
                    if (!id.includes('node_modules')) return undefined;

                    if (id.includes('bootstrap'))      return 'vendor-bootstrap';
                    if (id.includes('@popperjs'))      return 'vendor-bootstrap';
                    if (id.includes('@fortawesome'))   return 'vendor-fontawesome';
                    if (id.includes('chart.js'))       return 'vendor-charts';
                    if (id.includes('datatables.net')) return 'vendor-datatables';

                    return 'vendor';
                },
            },
        },

        chunkSizeWarningLimit: 800,
    },

    server: {
        host: '127.0.0.1',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
            protocol: 'ws',
        },
    },

    optimizeDeps: {
        include: ['bootstrap', 'chart.js/auto', 'datatables.net-dt', 'resources/js/app.js', 'resources/css/app.css' ],
    },
});
