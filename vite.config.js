import path from 'path'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
    // ubah batas warning dari default (500) jadi 1500 KB
    chunkSizeWarningLimit: 1500
  },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '192.168.12.104',
            port: 5173,
        },
    },
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        // 'resources/assets/css/app.css',
        // 'resources/assets/js/app.js',
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
