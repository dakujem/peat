<?php

declare(strict_types=1);

namespace Dakujem\Peat;

/**
 * Vite entry locator for development environments.
 *
 * This locator will check for the presence of a development server "tell" file once - upon the first entry lookup.
 * If it is present, it will attempt to read its contents for the server's URL.
 * A fallback URL may be provided for cases when the tell file does not contain a valid dev server URL.
 * It will then relay all calls to the `ViteServerLocator`.
 *
 * If the tell file is not present, all entry lookups will return `null` without further server detection.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class TellFileDevelopmentLocator implements ViteLocatorContract
{
    private string $tellFileName;
    private ?string $fallbackServerUrl;
    private ?ViteLocatorContract $locator = null;
    private bool $alreadyAttempted = false;

    public function __construct(
        string $tellFileName,
        ?string $fallbackServerUrl = null
    ) {
        $this->tellFileName = $tellFileName;
        $this->fallbackServerUrl = $fallbackServerUrl;
    }

    public function entry(string $name, ?string $relativeOffset = null): ?ViteEntryContract
    {
        if (!$this->alreadyAttempted) {
            $this->alreadyAttempted = true;
            $this->locator = $this->createLocatorIfServerDetected();
        }

        if (null === $this->locator) {
            return null;
        }

        // If the dev server's URL is known, all asset entries will be directed to it.
        return $this->locator->entry($name, $relativeOffset);
    }

    private function createLocatorIfServerDetected(): ?ViteLocatorContract
    {
        // The dev server is detected by the presence of a "tell" file.
        if (!file_exists($this->tellFileName)) {
            return null;
        }

        // The "tell" file may optionally contain a URL the dev server is listening to.
        $devServerUrl = file_get_contents($this->tellFileName);
        if (!filter_var($devServerUrl, FILTER_VALIDATE_URL)) {
            $devServerUrl = null;
        }

        // When no server URL is known, fall back to the provided URL, if any.
        $devServerUrl ??= $this->fallbackServerUrl;

        if ($devServerUrl === null) {
            return null;
        }

        // If the dev server's URL is known, all asset entries will be directed to it.
        return new ViteServerLocator($devServerUrl);
    }
}
