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
    /**
     * JavaScript modules to be imported.
     */
    public function modules(): array;

    /**
     * CSS files to be linked.
     */
    public function css(): array;

    /**
     * Other static assets referenced in the modules or CSS that may be preloaded.
     */
    public function assets(): array;
}
