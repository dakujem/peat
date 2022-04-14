<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use RuntimeException;
use Throwable;

/**
 * Serves file links from a Vite bundle.
 * Allows for pre-generation of a cache file for improved performance.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteBuildLocator implements ViteLocatorContract
{
    private ?array $map = null;
    private string $assetPath;
    private string $manifestFile;
    private string $cacheFile;
    private bool $strict;

    /**
     * @param string $manifestFile Path to the Vite-generated manifest json file.
     * @param string $cacheFile This is where this locator stores (and reads from) its cache file. Must be writable.
     * @param string $assetPath This will typically be relative path from the public dir to the dir with assets, or empty string ''.
     * @param bool $strict In strict mode (default), an exception is thrown on invalid read of the manifest file.
     */
    public function __construct(
        string $manifestFile,
        string $cacheFile,
        string $assetPath = '',
        bool $strict = true
    ) {
        $this->manifestFile = $manifestFile;
        $this->cacheFile = $cacheFile;
        $this->assetPath = $assetPath !== '' && $assetPath !== '/' ? rtrim($assetPath, '/') : '';
        $this->strict = $strict;
    }

    public function __invoke(string $entryName): ?ViteEntryAsset
    {
        return $this->entry($entryName);
    }

    /**
     * Returns an asset object for a given entry.
     * The asset object can be type cast to string containing HTML tags.
     *
     * @param string $name asset entry name, as found in the Vite-generated manifest.json
     * @return ViteEntryAsset|null
     */
    public function entry(string $name): ?ViteEntryAsset
    {
        $map = $this->loadAssetMap();
        $chunk = $map[$name] ?? null;
        if ($chunk === null) {
            return null;
        }
        $path = fn(?string $v): ?string => $v !== null ? $this->prefix($v, $this->assetPath) : null;
        $imports = array_filter(
            array_map(fn(string $import) => $path($map[$import]['file'] ?? null), $chunk['imports'] ?? [])
        );
        return new ViteEntryAsset(
            array_merge([$path($chunk['file']),], $imports),
            array_map($path, $chunk['css'] ?? []),
        );
    }

    private function prefix(string $path, ?string $prefix = null): string
    {
        return ($prefix !== null ? $prefix . '/' : '') . $path;
    }

    /**
     * Populates and writes a cache file for fast access in production environment.
     *
     * It is recommended to include this as a cache-warmup build step in the project's deployment/CI process.
     *
     * The (marginal) performance boost comes from not parsing the JSON manifest file, including a PHP file instead.
     */
    public function populateCache(): self
    {
        $map = $this->readManifest(
            $this->manifestFile,
            $this->strict,
        );
        $this->writeCacheFile($map, $this->cacheFile);
        return $this;
    }

    private function loadAssetMap(): array
    {
        $this->map ??= $this->loadFileOptionally($this->cacheFile);
        $this->map ??= $this->readManifest(
            $this->manifestFile,
            $this->strict,
        );
        return $this->map;
    }

    private function loadFileOptionally(?string $file) // :mixed
    {
        if ($file !== null) {
            $foo = @include $file;
            return $foo !== false ? $foo : null;
        }
        return null;
    }

    /**
     * Writes a PHP file containing the map.
     */
    private function writeCacheFile(array $map, string $cacheFile): void
    {
        $f = @fopen($cacheFile, 'w');
        if (!$f) {
            throw new RuntimeException('Vite cache file not writable: ' . $cacheFile);
        }
        $export = var_export($map, true);
        $content = "<?php\nreturn {$export};\n";
        try {
            fwrite($f, $content);
        } finally {
            is_resource($f) && fclose($f);
        }
    }

    /**
     * Read Vite-generated manifest JSON file as a PHP array.
     *
     * @param string $manifestFile
     * @param bool $strict when true, throws an exception upon error, otherwise an empty array is returned on errors
     * @return array
     */
    private function readManifest(string $manifestFile, bool $strict): array
    {
        $raw = @file_get_contents($manifestFile);
        if (!$raw && $strict) {
            throw new RuntimeException('Vite manifest not readable: ' . $manifestFile);
        }
        try {
            if ($raw) {
                return json_decode($raw, true);
            }
        } catch (Throwable $e) {
            if ($strict) {
                throw new RuntimeException('Invalid manifest JSON in file.', 0, $e);
            }
        }
        return [];
    }
}
