import path from 'path'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '192.168.12.53', // Pastikan alamat ini digunakan
            port: 5173, // Port yang sama dengan server
        },
        // proxy: {
        //     '/resources': {
        //         target: 'http://192.168.3.106:5173',
        //         changeOrigin: true,
        //         rewrite: (path) => path.replace(/^\/resources/, '/resources'),
        //     },
        // },
    },
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
      ],
      refresh: true,
    }),
  ],
  resolve: {
    alias: {
      '@tailwindConfig': path.resolve(__dirname, 'tailwind.config.js'),
    },
  },
  optimizeDeps: {
    include: [
      '@tailwindConfig',
    ]
  },
});
