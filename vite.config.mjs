import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    tailwindcss(), // ✅ First-party plugin
  ],
  build: {
    outDir: './public/build',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: './resources/js/app.js',
      },
      output: {
        assetFileNames: 'assets/[name][extname]',
      },
    },
  },
  server: {
    proxy: {
      '/': {
        target: 'http://localhost:8000', // Slim backend
        changeOrigin: true,
      },
    },
  },
});
