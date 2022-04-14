# Peat

PHP-vite bridge building tool, for any framework.

> 💿 `composer require dakujem/peat`


This tool helps integrate Vite-bundled JS apps into PHP-served web pages.

It provides a way to generate `<script>` and `<link>` tags to desired assets.


## Vite

Understanding of basics of how Vite works is essential for correct configuration.\
👉 [Vite](https://vitejs.dev)

For working integration, both `vite.config.js` and this bridge must be configured.


### Vite configuration

`build.manifest = true` - needed for integration.

`build.rollupOptions.input` must point to the `main.js` (or other JS entrypoint),
to override the default `index.html` entrypoint.

More info in the [Backend integration section](https://vitejs.dev/guide/backend-integration.html).

You may want to set `build.outDir` to point to a public dir's sub folder, so that you don't have to move the build files manually.


## Peat usage

Either `ViteBridge::makePassiveEntryLocator` friction reducer can be used, or custom entry locator setup can be composed.

To pregenerate cache for production, use `ViteBundleLocator::populateCache`.

To get asset URLs (or HTML tags), use the `ViteLocatorContract::entry` method.


## Example

> (JS sources are located in `/js/src` and the public dir is `/public`,
> `placeholder` may be replaced with any JS app/bundle name):
```js
// vite.config.js
import {defineConfig} from "vite";

export default defineConfig({
  build: {
    manifest: true,
    outDir: '../public/placeholder', // output directly to the public dir
    rollupOptions: {
      // overwrite default .html entry
      input: 'src/main.js',
    }
  }
});
```

```php
$vite = ViteBridge::makePassiveEntryLocator(
    manifestFile: ROOT_DIR . '/public/placeholder/manifest.json',
    cacheFile: TEMP_DIR . '/vite.php',   // can be any writable file
    assetPath: 'placeholder',   // path from /public to the dir where the manifest is located
    devServerUrl: $development ? 'http://localhost:3000' : null,
);

$html = (string) $vite->entry('src/main.js');
```

The result:
```html
<!-- PRODUCTION -->
<script type="module" src="/placeholder/assets/main.cf1f50e2.js"></script>
<script type="module" src="/placeholder/assets/vendor.5f8262d6.js"></script>
<link rel="stylesheet" href="/placeholder/assets/main.c9fc69a7.css" />

<!-- DEVELOPMENT -->
<script type="module" src="http://localhost:3000/@vite/client"></script>
<script type="module" src="http://localhost:3000/src/main.js"></script>
```

Note that you can also replace `placeholder` with an empty string ``,
then the manifest will be present directly in the public dir and the assets in the `/public/assets` dir,
which is the default.

