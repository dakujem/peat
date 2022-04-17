<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use RuntimeException;

/**
 * Static Vite entry locator factory.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteBridge
{
    /**
     * Returns a preconfigured asset entry locator.
     * The locator reads the manifest file (or cache file) and serves asset objects.
     *
     * If the $devServerUrl is not `null`, links to Vite dev server are returned.
     *
     * The $assetPath can be used to force absolute paths or set the base path. Ignored by the dev server.
     *
     * @param string $manifestFile Path to the Vite-generated manifest json file.
     * @param string $cacheFile This is where this locator stores (and reads from) its cache file. Must be writable.
     * @param string $assetPath This will typically be relative path from the public dir to the dir with assets, or empty string ''.
     * @param ?string $devServerUrl If passed, assets point to the Vite's dev server (development only).
     * @param bool $strict Locators throw exceptions in strict mode, silently fail in lax mode.
     * @return ViteLocatorContract
     */
    public static function makePassiveEntryLocator(
        string $manifestFile,
        string $cacheFile,
        string $assetPath = '',
        ?string $devServerUrl = null,
        bool $strict = false
    ): ViteLocatorContract {
        // If the dev server is on, all assets are served by the server.
        if ($devServerUrl !== null) {
            return new ViteServerLocator($devServerUrl);
        }
        // Otherwise, the assets are served from a bundle (build).
        $bundleLocator = new ViteBuildLocator($manifestFile, $cacheFile, $assetPath, $strict);
        if (!$strict) {
            return $bundleLocator;
        }
        // In strict mode, the final step is to throw an exception.
        return new CollectiveLocator(
            $bundleLocator,
            function (string $name) {
                throw new RuntimeException('Not found: ' . $name);
            },
        );
    }

    /**
     * @experimental
     * @deprecated This function is experimental. Use at your own risk. May be removed without prior notice.
     * @todo causes 1-2 second rendering hang.
     * @internal I really do not want you to use this :-)
     */
    public static function makeEntryLocatorWithServerDetector(
        string $manifestFile,
        string $cacheFile,
        string $assetPath = '', // tightly bound to Vite's `build.outDir`, if it targets the public dir's subdirs.
        ?string $devServerUrl = null,
        ?callable $detector = null,
        bool $strict = false
    ): ViteLocatorContract {
        if ($devServerUrl === null) {
            // No detection needed.
            return self::makePassiveEntryLocator($manifestFile, $cacheFile, $assetPath, null, $strict);
        }
        // First, try the dev server,
        // then the bundle,
        // finally, when strict, kick the bucket.
        return new CollectiveLocator(
            new ConditionalLocator(
                $detector ?? ViteServerDetector::usingCurl($devServerUrl),
                new ViteServerLocator($devServerUrl)
            ),
            new ViteBuildLocator($manifestFile, $cacheFile, $assetPath, $strict),
            $strict ? function (string $name) {
                throw new RuntimeException('Not found: ' . $name);
            } : null,
        );
    }
}
