import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [tailwindcss()],
  publicDir: false,
  // Keep Vite's cache inside project storage instead of node_modules.
  cacheDir: 'storage/framework/vite',
  build: {
    outDir: 'public/build',
    // Multiple entries may build into the same directory; `ui:build --clean`
    // owns removal instead of Vite emptying outputs it does not produce.
    emptyOutDir: false,
    rollupOptions: {
      input: {
        kit: 'resources/js/app.js',
        tailwind: 'resources/css/tailwind.css',
      },
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name][extname]',
      },
    },
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    watch: {
      ignored: [
        '**/node_modules/**',
        '**/.git/**',
        '**/storage/**',
        '**/public/build/**',
        '**/vendor/**',
      ],
    },
  },
});
