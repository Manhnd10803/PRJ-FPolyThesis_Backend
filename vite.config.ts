import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

const __dirname = path.dirname(new URL(import.meta.url).pathname);

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/styles/app.css', 'resources/app/index.tsx'],
      refresh: true,
    }),
    react(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/app'),
    },
  },
});
