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
    private ViteLocatorContract $serverLocator;

    public function __construct(callable $condition, ViteLocatorContract $locator)
    {
        $this->condition = $condition;
        $this->serverLocator = $locator;
    }

    public function __invoke(string $entryName): ?ViteEntryAsset
    {
        return $this->entry($entryName);
    }

    public function entry(string $name): ?ViteEntryAsset
    {
        return ($this->condition)($name) ? $this->serverLocator->entry($name) : null;
    }
}
