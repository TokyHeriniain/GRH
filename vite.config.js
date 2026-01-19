import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';



export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/main.jsx', 'resources/css/app.css'],
      refresh: true,
    }),
    react(),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
      '@css': path.resolve(__dirname, 'resources/css'),
      
    },
  },
  server: {
    host: '127.0.0.1', // ✅ évite l'adresse [::]
    port: 5173,
    strictPort: true, // ✅ force le port exact
    cors: true,
    proxy: {
      '/api': 'http://localhost:8000', // 👈 Ajoute ça !
    },
  },

});
