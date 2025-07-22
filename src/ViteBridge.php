<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use LogicException;
use RuntimeException;

/**
 * A factory service providing Vite entry locators.
 * Also provides an ability to populate a PHP cache file for performance improvement.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteBridge
{
    private string $manifestFile;
    private ?string $cacheFile;
    private string $assetPathPrefix;
    private ?string $devServerUrl;
    private bool $strict;

    /**
     * The $assetPath can be used to force absolute paths or set the base path. Ignored by the dev server.
     *
     * @param string $manifestFile Path to the Vite-generated manifest json file.
     * @param string|null $cacheFile This is where this locator stores (and reads from) its cache file. Must be writable.
     * @param string $assetPathPrefix This will typically be relative path from the public dir to the dir with assets, or empty string ''.
     * @param ?string $devServerUrl Vite's dev server URL (development only).
     * @param bool $strict Locators throw exceptions in strict mode, silently fail in lax mode.
     */
    public function __construct(
        string $manifestFile,
        ?string $cacheFile = null,
        string $assetPathPrefix = '',
        ?string $devServerUrl = null,
        bool $strict = false
    ) {
        $this->manifestFile = $manifestFile;
        $this->cacheFile = $cacheFile;
        $this->assetPathPrefix = $assetPathPrefix;
        $this->devServerUrl = $devServerUrl;
        $this->strict = $strict;
    }

    /**
     * Returns a preconfigured asset entry locator.
     * The method attempts to tell if the dev server is running by detecting the presence of a "tell" file.
     * Vite should be configured to create such a file when starting the dev server.
     *
     * @param bool $detectServer Set this to `false` in production or when the assets should always be located from a bundle.
     * @param string $tellFileName The location of the "tell" file.
     * @param bool $readContentsAsUrl Decide whether the contents of the tell file should be used as the URL of the dev server or set to `false` to use the constructor argument.
     */
    public function makeServerTellEntryLocator(
        bool $detectServer,
        string $tellFileName,
        bool $readContentsAsUrl = true
    ): ViteLocatorContract {
        if (!$detectServer) {
            // When the server detection is off, the assets are served from a bundle (build).
            return $this->makeBundleEntryLocator();
        }

        // The dev server is detected by the presence of a "tell" file.
        if (!file_exists($tellFileName)) {
            return $this->makeBundleEntryLocator();
        }

        // If the dev server is running, it serves all the assets.
        $url = null;

        // The "tell" file may optionally contain a URL the dev server is listening to.
        if ($readContentsAsUrl) {
            $url = file_get_contents($tellFileName);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $url = null;
            }
        }

        return $this->makeDevServerEntryLocator($url);
    }

    /**
     * Returns a preconfigured asset entry locator.
     * The locator reads the manifest file (or cache file) and serves asset objects.
     *
     * If the $useDevServer is `true`, links to Vite dev server are returned by the locator.
     *
     * This call does not detect whether the dev server is actually running or not (hence "passive").
     *
     * @param bool $useDevServer Set this to `false` in production or when the assets should be located from a bundle.
     */
    public function makePassiveEntryLocator(
        bool $useDevServer = false
    ): ViteLocatorContract {
        // If the dev server is used, it serves all the assets.
        if ($useDevServer) {
            return $this->makeDevServerEntryLocator();
        }
        // Otherwise, the assets are served from a bundle (build).
        return $this->makeBundleEntryLocator();
    }

    /**
     * Returns a preconfigured asset entry locator for development.
     * The locator returns entries pointing to the Vite development server for any asset being resolved.
     */
    public function makeDevServerEntryLocator(?string $devServerUrl = null): ViteLocatorContract
    {
        $devServerUrl ??= $this->devServerUrl;
        if ($devServerUrl === null) {
            throw new LogicException('The development server URL has not been provided.');
        }

        return new ViteServerLocator($devServerUrl);
    }

    /**
     * Returns a preconfigured asset entry locator for production builds.
     * The locator reads the manifest file (or cache file) and serves asset objects.
     */
    public function makeBundleEntryLocator(): ViteLocatorContract
    {
        $bundleLocator = new ViteBuildLocator(
            $this->manifestFile,
            $this->cacheFile,
            $this->assetPathPrefix,
            $this->strict,
        );
        if (!$this->strict) {
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
     * Populates a cache file to be included instead of parsing the Vite bundle's JSON manifest file.
     * Should be called during the deployment/CI process as one of the build steps.
     */
    public function populateCache(): void
    {
        (new ViteBuildLocator(
            $this->manifestFile,
            $this->cacheFile,
            $this->assetPathPrefix,
            $this->strict,
        ))
            ->populateCache();
    }
}
