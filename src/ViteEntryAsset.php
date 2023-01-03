<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use JsonSerializable;
use Stringable;

/**
 * Asset object containing links to JS modules and CSS.
 * The asset object can be type cast to string containing HTML tags or processed manually.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteEntryAsset implements Stringable, JsonSerializable
{
    private array $modules;
    private array $css;

    public function __construct(array $modules = [], array $css = [])
    {
        $this->modules = $modules;
        $this->css = $css;
    }

    public function modules(): array
    {
        return $this->modules;
    }

    public function css(): array
    {
        return $this->css;
    }

    public function __toString(): string
    {
        return implode("\n", array_merge(
            array_map(fn(string $m): string => "<script type=\"module\" src=\"{$m}\"></script>", $this->modules),
            array_map(fn(string $css): string => "<link rel=\"stylesheet\" href=\"{$css}\" />", $this->css),
        ));
    }

    public function jsonSerialize(): array
    {
        return [
            'modules' => $this->modules,
            'css' => $this->css,
        ];
    }
}
