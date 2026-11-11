<?php

declare(strict_types=1);


namespace Dakujem\Trest;

require_once __DIR__ . '/common.php';

use Dakujem\Peat\ViteBuildLocator;
use Tester\Assert;
use Tester\TestCase;

/**
 * This test tests that CSS of statically imported (shared) chunks is included with an entry.
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _SharedCssViteBuildLocatorTest extends TestCase
{
    private const CACHE = TEMP . '/vite.shared-css.php';

    protected function setUp()
    {
        if (file_exists(self::CACHE)) {
            unlink(self::CACHE);
        }
        parent::setUp();
    }

    public function testCssOfImportedChunkIsIncluded()
    {
        // `views/bar.js` has no CSS of its own, only the chunk it imports has
        $asset = $this->locator()->entry('views/bar.js');
        Assert::same(
            ['/w/assets/bar-gkvgaI9m.js', '/w/assets/shared-B7PI925R.js'],
            $asset->modules(),
        );
        Assert::same(
            ['/w/assets/shared-ChJ_j-JJ.css'],
            $asset->css(),
        );
        Assert::same(
            '<script type="module" src="/w/assets/bar-gkvgaI9m.js"></script>' . "\n" .
            '<script type="module" src="/w/assets/shared-B7PI925R.js"></script>' . "\n" .
            '<link rel="stylesheet" href="/w/assets/shared-ChJ_j-JJ.css" />',
            (string)$asset,
        );
    }

    public function testCssOfNestedImportsIsIncludedOnceInViteOrder()
    {
        // `views/foo.js` imports `shared` and `util`, `util` imports `shared` again and `deep`
        $asset = $this->locator()->entry('views/foo.js');
        Assert::same(
            ['/w/assets/foo-BRBmoGS9.js', '/w/assets/shared-B7PI925R.js', '/w/assets/util-Cq3tkE1n.js'],
            $asset->modules(),
        );
        Assert::same(
            [
                '/w/assets/shared-ChJ_j-JJ.css',
                '/w/assets/deep-BkTu2D8m.css',
                '/w/assets/foo-5UjPuW-k.css',
            ],
            $asset->css(),
        );
    }

    public function testDynamicImportsAreNotFollowed()
    {
        // `views/bar.js` imports `baz.js` dynamically, its CSS is loaded by Vite at runtime
        $asset = $this->locator()->entry('views/bar.js');
        Assert::false(in_array('/w/assets/baz-Dl3rWKq5.css', $asset->css(), true));

        $asset = $this->locator()->entry('baz.js');
        Assert::same(['/w/assets/baz-Dl3rWKq5.css'], $asset->css());
    }

    public function testRelativeOffset()
    {
        // relative offset is meant for relative asset path prefixes
        $locator = new ViteBuildLocator(FIXTURES . '/manifest.shared-css.json', null, 'w');
        $asset = $locator->entry('views/bar.js', '..');
        Assert::same(['../w/assets/shared-ChJ_j-JJ.css'], $asset->css());
    }

    public function testCachedMapGivesTheSameResult()
    {
        $expected = $this->locator()->entry('views/foo.js')->jsonSerialize();

        $this->locator(self::CACHE)->populateCache();
        Assert::true(file_exists(self::CACHE));

        // the manifest path is invalid, so the map can only come from the cache file
        $cached = new ViteBuildLocator(FIXTURES . '/does-not-exist.json', self::CACHE, '/w');
        Assert::same($expected, $cached->entry('views/foo.js')->jsonSerialize());
    }

    private function locator(?string $cacheFile = null): ViteBuildLocator
    {
        return new ViteBuildLocator(FIXTURES . '/manifest.shared-css.json', $cacheFile, '/w');
    }
}

// run the test
(new _SharedCssViteBuildLocatorTest)->run();
