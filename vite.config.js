import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    // Generate manifest.json in outDir
    manifest: true,
    outDir: 'dist',
    rollupOptions: {
      // Overwrite default .html entry
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
      },
    },
  },
  server: {
    // Vite dev server settings for backend integration
    strictPort: true,
    port: 5173,
    origin: 'http://localhost:5173',
  },
});
