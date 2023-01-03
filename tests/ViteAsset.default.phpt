<?php

declare(strict_types=1);


namespace Dakujem\Trest;

require_once __DIR__ . '/common.php';

use Dakujem\Peat\ViteEntryAsset;
use Tester\Assert;
use Tester\TestCase;

/**
 * This test tests the capabilities on the official Vite backend integration example.
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _DefaultViteAssetTest extends TestCase
{
    public function testEmptyAsset()
    {
        $asset = new ViteEntryAsset();

        Assert::same('', (string)$asset);
        Assert::same([], $asset->modules());
        Assert::same([], $asset->css());
        Assert::same(['modules' => [], 'css' => [],], $asset->jsonSerialize());
    }

    public function testNonEmptyAsset()
    {
        $asset = new ViteEntryAsset(['a', 'b'], ['c', 'd']);

        Assert::same('<script type="module" src="a"></script>' . "\n" .
            '<script type="module" src="b"></script>' . "\n" .
            '<link rel="stylesheet" href="c" />' . "\n" .
            '<link rel="stylesheet" href="d" />'
            , (string)$asset);
        Assert::same(['a', 'b'], $asset->modules());
        Assert::same(['c', 'd'], $asset->css());
        Assert::same(['modules' => ['a', 'b'], 'css' => ['c', 'd'],], $asset->jsonSerialize());
    }
}

// run the test
(new _DefaultViteAssetTest)->run();
