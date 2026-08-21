import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

/**
 * Vite 8 Configuration for PHP/Twig Backend Integration
 * --------------------------------------------------------------------------
 * - Bundles frontend TypeScript and Tailwind CSS v4 assets into dist/.
 * - Emits dist/.vite/manifest.json consumed by Core\ViteHelper in PHP.
 * - Enforces strict port 5173 binding and origin for local HMR.
 */
export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '@assets': resolve(__dirname, 'assets/js'),
    },
  },
  build: {
    // Generate manifest in outDir (dist/.vite/manifest.json)
    manifest: true,
    outDir: 'dist',
    chunkSizeWarningLimit: 600,
    rollupOptions: {
      // Overwrite default .html entry with modular TypeScript entry
      input: {
        app: resolve(__dirname, 'resources/js/app.ts'),
      },
    },
  },
  server: {
    // Vite dev server settings for PHP backend integration
    strictPort: true,
    port: 5173,
    origin: 'http://localhost:5173',
  },
});
