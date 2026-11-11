// Build used to generate the manifest fixtures for Peat tests, see readme.md.
// No `import ... from "vite"` here, so that one config works with any Vite version run via npx.
export default {
  build: {
    manifest: true,
    outDir: 'dist',
    emptyOutDir: true,
    assetsInlineLimit: 0, // emit the images as files, so that they appear in the manifest
    rollupOptions: { // `rolldownOptions` in Vite 8, the old name still works and older versions need it
      input: ['src/main.js', 'src/other.js', 'src/standalone.css'],
    },
  },
};
