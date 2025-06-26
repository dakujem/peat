<?php

declare(strict_types=1);

namespace Dakujem\Peat;

/**
 * Asset object containing links to JS modules and CSS.
 * The asset object can be type-cast to string containing HTML tags for the <head> tag or processed manually.
 *
 * Note: This entry only dumps its JS and CSS tags, not the other static assets from the manifest. Those have to be processed manually.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteEntryAsset implements ViteEntryContract
{
    private array $modules;
    private array $css;
    private array $assets;

    public function __construct(
        array $modules = [],
        array $css = [],
        array $assets = []
    ) {
        $this->modules = $modules;
        $this->css = $css;
        $this->assets = $assets;
    }

    public function modules(): array
    {
        return $this->modules;
    }

    public function css(): array
    {
        return $this->css;
    }

    /**
     * Note that these static assets are NOT part of the exported tags when casting to string.
     */
    public function assets(): array
    {
        return $this->assets;
    }

    public function __toString(): string
    {
        // Note: The static assets may be preloaded manually but are not rendered here automatically.
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
            'assets' => $this->assets,
        ];
    }
}
