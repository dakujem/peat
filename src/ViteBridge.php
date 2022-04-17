<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use LogicException;
use RuntimeException;

/**
 * Vite entry locator factory.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteBridge
{
    private string $manifestFile;
    private string $cacheFile;
    private string $assetPath;
    private ?string $devServerUrl;
    private bool $strict;

    /**
     * The $assetPath can be used to force absolute paths or set the base path. Ignored by the dev server.
     *
     * @param string $manifestFile Path to the Vite-generated manifest json file.
     * @param string $cacheFile This is where this locator stores (and reads from) its cache file. Must be writable.
     * @param string $assetPath This will typically be relative path from the public dir to the dir with assets, or empty string ''.
     * @param ?string $devServerUrl Vite's dev server URL (development only).
     * @param bool $strict Locators throw exceptions in strict mode, silently fail in lax mode.
     */
    public function __construct(
        string $manifestFile,
        string $cacheFile,
        string $assetPath = '',
        ?string $devServerUrl = null,
        bool $strict = false
    ) {
        $this->manifestFile = $manifestFile;
        $this->cacheFile = $cacheFile;
        $this->assetPath = $assetPath;
        $this->devServerUrl = $devServerUrl;
        $this->strict = $strict;
    }

    /**
     * Returns a preconfigured asset entry locator.
     * The locator reads the manifest file (or cache file) and serves asset objects.
     *
     * If the $useDevServer is `true`, links to Vite dev server are returned by the locator.
     */
    public function makePassiveEntryLocator(
        bool $useDevServer = false
    ): ViteLocatorContract {
        // If the dev server is used, all assets are served by the server.
        if ($useDevServer) {
            if ($this->devServerUrl === null) {
                throw new LogicException('The development server URL has not been provided.');
            }
            return new ViteServerLocator($this->devServerUrl);
        }
        // Otherwise, the assets are served from a bundle (build).
        $bundleLocator = new ViteBuildLocator($this->manifestFile, $this->cacheFile, $this->assetPath, $this->strict);
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
     * Populates a cache file that is included instead of parsing a JSON manifest.
     * Should be called during the deployment/CI process as one of the build steps.
     */
    public function populateCache(): void
    {
        (new ViteBuildLocator($this->manifestFile, $this->cacheFile, $this->assetPath, $this->strict))
            ->populateCache();
    }
}
