# Vite build fixtures

The sources in this folder are built with real Vite versions
to generate the manifest fixtures `tests/fixtures/manifest.vite<version>.json`.
The fixtures are used by `tests/ViteBuildLocator.viteBuilds.phpt`.

The build has three entries:
- `src/main.js` imports a shared chunk that has its own CSS, has its own CSS and an image, and imports `src/lazy.js` dynamically
- `src/other.js` imports the shared chunk only
- `src/standalone.css` is a CSS-only entry


## Regenerate the fixtures

Requires Node.js 20.19+ or 22.12+ (needed by Vite 8).
Nothing has to be installed, `npx` downloads each Vite version.

```
cd tests/vite
npm run fixtures
```

This builds the sources with Vite 2 through 8 and copies each manifest to `tests/fixtures`.
To regenerate selected versions only, run `node fixtures.mjs 7 8`.
The build output goes to `tests/vite/dist` (ignored by git).

The test reads the expected file names from the manifests,
so regenerated fixtures do not require changes to the test.

The fixtures were last generated with Vite 2.9.18, 3.2.11, 4.5.14, 5.4.21, 6.4.6, 7.3.8 and 8.3.8 on Node.js 22.12.

Notes:
- Vite 2 does not list the CSS-only entry in the manifest.
- Vite 5 writes a key with an unresolved placeholder for the shared CSS (`_shared-!~{005}~.js`). That is Vite's output, keep it.


## Add a new Vite version

1. Run `node fixtures.mjs <major-version>`, e.g. `node fixtures.mjs 9`.
2. Add the version to `fixtures` script in `package.json`.
3. Add the version to `_ViteBuildsTest::versions()` in `tests/ViteBuildLocator.viteBuilds.phpt`.
4. Run the tests (`composer test` in the project root).
