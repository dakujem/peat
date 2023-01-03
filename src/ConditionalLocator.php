<?php

declare(strict_types=1);

namespace Dakujem\Peat;

/**
 * A conditional wrapper for a locator.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ConditionalLocator implements ViteLocatorContract
{
    /** @var callable */
    private $condition;
    private ViteLocatorContract $locator;

    public function __construct(callable $condition, ViteLocatorContract $locator)
    {
        $this->condition = $condition;
        $this->locator = $locator;
    }

    public function __invoke(string $entryName): ?ViteEntryAsset
    {
        return $this->entry($entryName);
    }

    public function entry(string $name, ?string $relativeOffset = null): ?ViteEntryAsset
    {
        return ($this->condition)($name, $relativeOffset) ?
            $this->locator->entry($name, $relativeOffset) :
            null;
    }
}
