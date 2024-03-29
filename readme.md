# Peat

![PHP from Packagist](https://img.shields.io/packagist/php-v/dakujem/peat)
[![Test Suite](https://github.com/dakujem/peat/actions/workflows/php-test.yml/badge.svg)](https://github.com/dakujem/peat/actions/workflows/php-test.yml)
[![Coverage Status](https://coveralls.io/repos/github/dakujem/peat/badge.svg?branch=feat/friction-reducer)](https://coveralls.io/github/dakujem/peat?branch=feat/friction-reducer)

PHP-Vite bridge building tool, for any framework.

> 💿 `composer require dakujem/peat`


This tool helps integrate Vite-bundled JS apps into PHP-served web pages.

It provides a way to generate `<script>` and `<link>` tags to desired assets.


## Vite

Vite (a.k.a. Vite.js) consists of 2 parts:
- _development server_
- and _bundler_.

To integrate a JS app, the backend must output snippets like these:
- files from a _bundle_ (production)
    ```html
    <!-- PRODUCTION -->
    <script type="module" src="/placeholder/assets/main.cf1f50e2.js"></script>
    <script type="module" src="/placeholder/assets/vendor.5f8262d6.js"></script>
    <link rel="stylesheet" href="/placeholder/assets/main.c9fc69a7.css" />
    ```
- links to files served by _development server_
    ```html
    <!-- DEVELOPMENT -->
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module" src="http://localhost:5173/src/main.js"></script>
    ```

For best development experience, we only want to care about `main.js` as the entry point of the JS app in both cases.\
Ideally, we want to achieve something like the following:
```html
  <!-- PHP template -->
  <?php echo vite('main.js'); ?>
  
  <!-- or other templating systems, like Twig -->
  {{ vite('main.js') }}
```

To achieve this, Peat reads the _manifest_ JSON file generated by Vite for each bundle.\
In development, we simply serve the entrypoint (along with `@vite/client` supporting library),
and the browser will load the rest of the files as ES modules.


### Vite configuration

Vite (`vite.config.js`) must be configured to output a _manifest_ file and override the default entrypoint:

- `build.manifest` must be set to `true`
- `build.rollupOptions.input` should point to the `main.js` (or other JS entrypoint)

More info in the [Vite's Backend integration guide](https://vitejs.dev/guide/backend-integration.html).

> 💡
> 
> You may also want to set `build.outDir` to point to a sub folder in the backend's public dir,
> so that you don't have to move the build files manually after each build.


### Troubleshooting configuration

The simplest way to test if the configuration works is by dropping these snippets
into your PHP-served HTML templates and observing the output.

Start the development servers (both Vite `npm run serve` and PHP),\
then drop this into the HTML template:
```php
<?php echo Dakujem\Peat\ViteHelper::populateDevelopmentAssets('src/main.js', 'http://localhost:5173'); ?>
```
It should produce `<script>` tags to development assets and the JS app should load from the server.\
If it does not, check the entry name and the Vite server URL and port.
The entry name should align with `build.rollupOptions.input` option.

Next, to test a bundle,\
build a bundle by running `npm run build`,\
move the dist files into your PHP server public root directory (or configure `build.outDir` option),\
then replace the previous snippet with this one (replace `my-js-widget` with a proper dir):
```php
<?php echo Dakujem\Peat\ViteHelper::extractAssets('src/main.js', './my-js-widget/manifest.json', '/my-js-widget'); ?>
```
It should produce `<script>` and `<link>` tags for your JS and CSS.\
Pay attention to the path to the manifest file, as it will change according to where you run the snippet from. Adjust as needed.\
Understand that `'./my-js-widget/manifest.json'` is a server path, while `'/my-js-widget'` is part of a URL prefixing the assets
(that is, where you moved the dist files to, relative to the PHP script in your public root).\
Also note that '/my-js-widget' is absolute, you may need to add your project's base path or use relative offsets (see below).

Once this works, I suggest you move on to configure the bridge service (see below).

> 💡
>
> If none of the above works, read the [Vite's Backend integration guide](https://vitejs.dev/guide/backend-integration.html),
> try to figure out what HTML serves your JS app correctly,
> then compare it to what Peat outputs
> and tweak the variables accordingly.


## Bridge usage

The most straight-forward way is to register `ViteBridge` as a service in your service container.

Depending on your running environment,
this service would create a suitable "entry locator" (`ViteLocatorContract`),
which populates assets for Vite entries.

To get asset URLs (or HTML tags), use the `ViteLocatorContract::entry` method (see the example below).


### Example

Assume JS sources are located in `<project>/js/src` and the public dir is `<project>/public`,
`my-js-widget` may be replaced with any path.

Configure Vite like this:
```js
// vite.config.js
import {defineConfig} from "vite";

export default defineConfig({
  build: {
    manifest: true,
    outDir: '../public/my-js-widget', // output directly to the public dir
    rollupOptions: {
      // overwrite default .html entrypoint
      input: 'src/main.js',
    }
  }
});
```

Configure `ViteBridge` service along these lines:
```php
$bridgeService = new ViteBridge(
    manifestFile: ROOT_DIR . '/public/my-js-widget/manifest.json',
    cacheFile: TEMP_DIR . '/vite.php',   // can be any writable file
    assetPathPrefix: '/my-js-widget',   // all asset paths from the manifest will be prefixed by this value
    devServerUrl: 'http://localhost:5173',
);
```

And use it directly:
```php
$locator = $bridgeService->makePassiveEntryLocator(useDevServer: $isDevelopment);
$html = (string) $locator->entry('src/main.js');
```

Then later in a template
```php
<head>
  <?php echo $html; ?>
</head>
```

The above will feed all the necessary HTML tags for `main.js` entrypoint to the `$html` variable,
for both the _devleopment server_ and any _bundle_ (depending on the `$isDevelopment` variable).

You may want to register a method that uses the locator to be called from within your templates, something like this
```php
$vite = function (string $entryName) use ($locator) {
    return $locator->entry($entryName)
}
```

Then later in a template you would only call
```php
<head>
  <?php echo $vite('src/main.js'); ?>
</head>
```

In Twig or Latte, for example, you may register a filter to be used as follows
```twig
<head>
  {{ vite('src/main.js') }}
</head>
```


## Production setup

In production environments, performance is critical.

To avoid reading and parsing the JSON manifest file on every request,
Peat allows you to parse the JSON manifest contents once and export them as a PHP file.
Peat then includes that optimized file instead of reading the JSON manifest.

> 💡
>
> Be sure to enable this caching mechanism in production environments.
> In high load scenarios, including a tiny PHP file is **much faster** than parsing a JSON file on every request.

To populate the cache file for production, call `ViteBundleLocator::populateCache`.
```php
$bridgeService->populateCache();
```

However,
this file **must be re-populated** every time the manifest file changes (on every Vite build).

This is achieved by calling `ViteBuildLocator::populateCache()`
as one of the build steps during the deployment/CI process.

> If you are not using a deployment pipeline or CI for deployment,
> I suggest you compare file timestamps of the cache file and the manifest file,
> or include the cache file in your cache-purging process.


## Handling relative paths

So far we used **absolute paths** to the assets. Which is **recommended**.

> 💡
>
> The `assetPathPrefix` should contain the **project's base path** plus path to the manifest file and should be absolute.

However, if you need to use relative paths, you are able to.

`ViteLocatorContract::entry` method accepts second parameter called **"relative offset"**,
which is designed for cases where `assetPathPrefix` needs to be prefixed per-call.

The parameter should be used in scripts that are not located in public document root.\
The parameter should typically contain strings like `..`, `../..`, etc. leading to the public root.

Do not use this parameter when using absolute paths, as it will break the generated URIs.


## Advanced use

Instead of using `ViteBridge::makePassiveEntryLocator` friction reducer,
a custom entry locator setup can be composed.
The locator must implement `ViteLocatorContract` interface.

Two locators are provided to help you compose your own locator setup:
- `CollectiveLocator` works with plain callables or other locators to create a locator stack (fallback)
- `ConditionalLocator` allows to add runtime conditions to enable/disable a locator in a stack

See `ViteBridge` source code for inspiration.


## Compatibility

Please note that this tool (Peat) is tightly coupled with the workings of Vite.

Currently, Peat supports Vite versions `v2`, `v3`, `v4` and later.

| PHP       | Peat | Vite.js   |
|:----------|:-----|:----------|
| 7.4 - 8.* | 1.*  | 2.* - 4.* |

> Unless there is a breaking change in the way Vite.js generates its `manifest.json` file,
> Peat will remain compatible with future versions of Vite.js.


## Integrations

- [Vitte: Vite bridge for Latte templates (Nette Framework)](https://github.com/viaaurea/vitte)
