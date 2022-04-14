<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use InvalidArgumentException;

/**
 * Locator composed of multiple other locators attempting to return the entry in sequence.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class CollectiveLocator implements ViteLocatorContract
{
    private array $locators;

    public function __construct(/*ViteLocatorContract|callable|null*/ ...$locators)
    {
        $this->locators = array_filter($locators);
        foreach ($this->locators as $locator) {
            if (!$locator instanceof ViteLocatorContract && !is_callable($locator)) {
                throw new InvalidArgumentException(sprintf(
                    'Each locator must either implement %1$s or be callable with the same signature as %1$s::entry.',
                    ViteLocatorContract::class,
                ));
            }
        }
    }

    public function entry(string $name): ?ViteEntryAsset
    {
        $entryName = ltrim($name, '/');
        foreach ($this->locators as $step) {
            $asset = $step instanceof ViteLocatorContract ? $step->entry($entryName) : $step($entryName);
            if ($asset !== null) {
                return $asset;
            }
        }
        return null;
    }
}
