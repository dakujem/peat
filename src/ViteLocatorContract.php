<?php

declare(strict_types=1);

namespace Dakujem\Peat;

/**
 * Interface for services able to locate asset entries.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
interface ViteLocatorContract
{
    public function entry(string $name, ?string $relativeOffset = null): ?ViteEntryAsset;
}
