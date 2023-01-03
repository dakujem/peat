<?php

declare(strict_types=1);

namespace Dakujem\Peat;

/**
 * Serves links to local dev server.
 *
 * Setting the server URL to `null` turns the locator off, otherwise it always returns an asset URL.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteServerLocator implements ViteLocatorContract
{
    public const CLIENT_SCRIPT = '@vite/client';

    private ?string $liveHost;

    public function __construct(?string $liveHostUrl)
    {
        $this->liveHost = $liveHostUrl !== null ? rtrim($liveHostUrl, '/') : null;
    }

    public function __invoke(string $entryName): ?ViteEntryAsset
    {
        return $this->entry($entryName);
    }

    /**
     * If the server URL is set, this call always returns a URL, because it does not detect existence of the asset.
     */
    public function entry(string $name, ?string $relativeOffset = null): ?ViteEntryAsset
    {
        if ($this->liveHost !== null) {
            // Note: Relative offset is ignored in this setup on purpose.
            return $this->serveLive($name, $this->liveHost);
        }
        return null;
    }

    private function serveLive(string $asset, string $host): ViteEntryAsset
    {
        // Note:
        //   Vite dev-server serves the files as if they were in a root dir. It ignores path prefixes.
        //   Vite dev-server is just a static server operating on the JS app's sources.
        return new ViteEntryAsset(
            [
                $host . '/' . self::CLIENT_SCRIPT,
                $host . '/' . $asset,
//                $host . '/' . ltrim($asset, '/'), // this would not be coherent with build locator
            ],
        );
    }
}
