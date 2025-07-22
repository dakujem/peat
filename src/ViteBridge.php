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
    private ?bool $strict;

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
        ?bool $strict = null
    ) {
        $this->manifestFile = $manifestFile;
        $this->cacheFile = $cacheFile;
        $this->assetPathPrefix = $assetPathPrefix;
        $this->devServerUrl = $devServerUrl;
        $this->strict = $strict;
    }

    // TODO create a separate friction reducer
    public static function populateEntryLocator(
        bool $detectDevelopmentServer,
        string $devServerTellFile,
        string $manifestFile,
        ?string $cacheFile = null,
        string $assetPathPrefix = '',
        ?string $devServerUrl = null,
        ?bool $strict = null
    ): ViteLocatorContract {
        $locators = [];

        // The detection should not run in production.
        if ($detectDevelopmentServer) {
            $locators[] = new TellFileDevelopmentLocator(
                $devServerTellFile,
                $devServerUrl,
            );
        }

        // Assume strict mode by default only when development server detection is on.
        // The detection should only be on in development environments.
        $strict ??= $detectDevelopmentServer;

        // The bundle locator is always active and is the default.
        // When the dev server detection is off, the assets are served from a bundle (build).
        $locators[] = new ViteBuildLocator(
            $manifestFile,
            $cacheFile,
            $assetPathPrefix,
            $strict,
        );

        if ($strict) {
            // In strict mode, the final step is to throw an exception.
            $locators[] = function (string $name) {
                throw new RuntimeException('Not found: ' . $name);
            };
        }

        // This is a micro optimization: When there is only a single detector (the `ViteBuildLocator`), return it directly.
        if (count($locators) === 1) {
            return $locators[0];
        }

        return new CollectiveLocator(...$locators);
    }

    /**
     * Returns a preconfigured Vite asset entry locator.
     * The method attempts to tell if a dev server is running by detecting the presence of a "tell" file.
     * Vite should be configured to create such a file when starting the dev server.
     *
     * In other cases use the `makePassiveEntryLocator` to manually switch between server and bundles.
     *
     * @param bool $detectServer Set this to `false` in production or when the assets should always be located from a bundle.
     * @param string $tellFileName The location of the "tell" file.
     */
    public function makeEntryLocator(
        bool $detectServer,
        string $tellFileName
    ): ViteLocatorContract {
        return self::populateEntryLocator(
            $detectServer,
            $tellFileName,
            $this->manifestFile,
            $this->assetPathPrefix,
            $this->devServerUrl,
            $this->cacheFile,
            $this->strict ?? true, // Note: Strict mode default is `true` here for compatibility reasons only.
        );
    }

    /**
     * Returns a preconfigured asset entry locator.
     * This call does not detect whether the dev server is actually running or not (hence "passive").
     * Use the `$useDevServer` parameter to switch between Vite development server and bundle.
     *
     * If the `$useDevServer` is `true`, links to Vite dev server are returned by the locator.
     * Otherwise, the returned locator reads the manifest file (or cache file) and serves asset objects.
     *
     * @param bool $useDevServer Set this to `false` in production and when the assets should be located from a bundle. Set to `true` to use the development server.
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
        $strict = $this->strict ?? true; // Note: Strict mode default is `true` here for compatibility reasons only.
        $bundleLocator = new ViteBuildLocator(
            $this->manifestFile,
            $this->cacheFile,
            $this->assetPathPrefix,
            $strict,
        );
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
     * Populates a cache file to be included instead of parsing the Vite bundle's JSON manifest file.
     * Should be called during the deployment/CI process as one of the build steps.
     */
    public function populateCache(): void
    {
        (new ViteBuildLocator(
            $this->manifestFile,
            $this->cacheFile,
            $this->assetPathPrefix,
            $this->strict ?? true,
        ))
            ->populateCache();
    }
}
