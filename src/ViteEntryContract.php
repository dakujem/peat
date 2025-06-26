<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use JsonSerializable;
use Stringable;

/**
 * Interface for asset objects containing links to JS modules and CSS assets.
 * The asset objects can be type cast to string containing HTML tags.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
interface ViteEntryContract extends Stringable, JsonSerializable
{
    public function modules(): array;

    public function css(): array;
}
