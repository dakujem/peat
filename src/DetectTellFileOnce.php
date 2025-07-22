<?php

declare(strict_types=1);

namespace Dakujem\Peat;

/**
 * This locator will check for the presence of a development server "tell" file once - upon the first entry lookup.
 * If it is present, it will attempt to read its contents for the server's URL.
 * A fallback URL may be provided for cases when the tell file does not contain a valid dev server URL.
 * It will then relay all calls to the `ViteServerLocator`.
 *
 * If the tell file is not present, all entry lookups will return `null` without further server detection.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class DetectTellFileOnce implements ViteLocatorContract
{
    private string $tellFileName;
    private ?ViteLocatorContract $locator = null;
    private bool $alreadyAttempted = false;
    private ?string $fallbackServerUrl;

    public function __construct(
        string $tellFileName,
        ?string $fallbackServerUrl = null
    ) {
        $this->tellFileName = $tellFileName;
        $this->fallbackServerUrl = $fallbackServerUrl;
    }

    public function entry(string $name, ?string $relativeOffset = null): ?ViteEntryContract
    {
        if ($this->alreadyAttempted) {
            return $this->locateEntryOrBail($name, $relativeOffset);
        }

        $this->alreadyAttempted = true;

        // The dev server is detected by the presence of a "tell" file.
        if (!file_exists($this->tellFileName)) {
            return $this->locateEntryOrBail($name, $relativeOffset);
        }

        // The "tell" file may optionally contain a URL the dev server is listening to.
        $devServerUrl = file_get_contents($this->tellFileName);
        if (!filter_var($devServerUrl, FILTER_VALIDATE_URL)) {
            $devServerUrl = null;
        }

        // When no server URL is known, fall back to the provided URL, if any.
        $devServerUrl ??= $this->fallbackServerUrl;

        // If the dev server's URL is known, all asset entries will be directed to it.
        if ($devServerUrl !== null) {
            $this->locator = new ViteServerLocator($devServerUrl);
        }

        return $this->locateEntryOrBail($name, $relativeOffset);
    }

    private function locateEntryOrBail(string $name, ?string $relativeOffset = null): ?ViteEntryContract
    {
        if (null === $this->locator) {
            return null;
        }

        return $this->locator->entry($name, $relativeOffset);
    }
}