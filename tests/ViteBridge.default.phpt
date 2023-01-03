<?php

declare(strict_types=1);


namespace Dakujem\Trest;

require_once __DIR__ . '/common.php';

use Dakujem\Peat\ViteBridge;
use LogicException;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

/**
 * This test tests the capabilities on the official Vite backend integration example.
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _DefaultExampleViteBridgeTest extends TestCase
{
    protected function setUp()
    {
        if (file_exists(TEMP . '/vite.default.php')) {
            unlink(TEMP . '/vite.default.php');
        }
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testInvalidDevConfiguration()
    {
        $bridgeService = new ViteBridge('foo');
        Assert::throws(fn() => $bridgeService->makePassiveEntryLocator(true), LogicException::class);
    }

    public function testInvalidProductionConfiguration()
    {
        $bridgeService = new ViteBridge('foo');
        Assert::throws(fn() => $bridgeService->populateCache(), LogicException::class);
    }

    public function testEntryNotFoundReturnsNull()
    {
        $bridgeService = new ViteBridge('foo'); // the manifest does not exist at this location
        $locator = $bridgeService->makePassiveEntryLocator();
        Assert::same(null, $locator->entry('foo'));

        // even the leading slash must not be present
        Assert::same(null, $locator->entry('/main.js'));
    }

    public function testInvalidManifestWillNotThrowInLaxMode()
    {
        // lax mode is the default
        $bridgeService = new ViteBridge(
            FIXTURES . '/invalid.json',
        );
        $locator = $bridgeService->makePassiveEntryLocator();
        Assert::same(null, $locator->entry('main.js'));
    }

    public function testInvalidManifestWillThrowInStrictMode()
    {
        $bridgeService = new ViteBridge(
            FIXTURES . '/invalid.json',
            TEMP . '/vite.default.php',
            '',
            null,
            true,
        );
        $locator = $bridgeService->makePassiveEntryLocator();
        Assert::throws(fn() => $locator->entry('main.js'), RuntimeException::class);
    }

    public function testDevelopmentServerLocator()
    {
        $bridgeService = $this->getBridgeService();
        $locator = $bridgeService->makePassiveEntryLocator(true);

        // main entry
        $assets = $locator->entry('main.js');
        Assert::notNull($assets, 'Server locator not working');
        Assert::same(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>' .
            "\n" .
            '<script type="module" src="http://localhost:5173/main.js"></script>',
            (string)$assets,
            'Localhost url of dev server not provided',
        );

        // secondary entry
        $assets = $locator->entry('views/foo.js');
        Assert::notNull($assets, 'Server locator not working');
        Assert::same(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>' .
            "\n" .
            '<script type="module" src="http://localhost:5173/views/foo.js"></script>',
            (string)$assets,
            'Localhost url of dev server not provided',
        );
    }

    public function testBundleLocator()
    {
        $bridgeService = $this->getBridgeService();
        $locator = $bridgeService->makePassiveEntryLocator(false);

        // main entry
        $assets = $locator->entry('main.js');
        Assert::notNull($assets, 'Build locator not working');
        Assert::same(
            '<script type="module" src="my-js-widget/assets/main.4889e940.js"></script>' .
            "\n" .
            '<link rel="stylesheet" href="my-js-widget/assets/main.b82dbe22.css" />',
            (string)$assets,
            'Bundle asset URLs don\'t work',
        );

        // secondary entry
        $assets = $locator->entry('views/foo.js');
        Assert::notNull($assets, 'Build locator not working');
        Assert::same(
            '<script type="module" src="my-js-widget/assets/foo.869aea0d.js"></script>' .
            "\n" .
            '<script type="module" src="my-js-widget/assets/shared.83069a53.js"></script>',
            (string)$assets,
            'Bundle asset URLs don\'t work',
        );
    }

    public function testDevelopmentServerLocatorWithOffset()
    {
        $bridgeService = $this->getBridgeService();
        $locator = $bridgeService->makePassiveEntryLocator(true);

        // main entry (offsets are ignored)
        $assets = $locator->entry('main.js', '../..');
        Assert::notNull($assets, 'Server locator not working');
        Assert::same(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>' .
            "\n" .
            '<script type="module" src="http://localhost:5173/main.js"></script>',
            (string)$assets,
            'Localhost url of dev server not provided',
        );

        // secondary entry (offsets are ignored)
        $assets = $locator->entry('views/foo.js', '../..');
        Assert::notNull($assets, 'Server locator not working');
        Assert::same(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>' .
            "\n" .
            '<script type="module" src="http://localhost:5173/views/foo.js"></script>',
            (string)$assets,
            'Localhost url of dev server not provided',
        );
    }

    public function testBundleLocatorWithOffset()
    {
        $bridgeService = $this->getBridgeService();
        $locator = $bridgeService->makePassiveEntryLocator(false);

        // main entry
        $assets = $locator->entry('main.js', '../..');
        Assert::notNull($assets, 'Build locator not working');
        Assert::same(
            '<script type="module" src="../../my-js-widget/assets/main.4889e940.js"></script>' .
            "\n" .
            '<link rel="stylesheet" href="../../my-js-widget/assets/main.b82dbe22.css" />',
            (string)$assets,
            'Bundle asset URLs don\'t work',
        );

        // secondary entry
        $assets = $locator->entry('views/foo.js', '../..');
        Assert::notNull($assets, 'Build locator not working');
        Assert::same(
            '<script type="module" src="../../my-js-widget/assets/foo.869aea0d.js"></script>' .
            "\n" .
            '<script type="module" src="../../my-js-widget/assets/shared.83069a53.js"></script>',
            (string)$assets,
            'Bundle asset URLs don\'t work',
        );
    }

    private function getBridgeService()
    {
        return new ViteBridge(
            FIXTURES . '/manifest.official-example.json',
            TEMP . '/vite.default.php',
            'my-js-widget',
            'http://localhost:5173',
            true,
        );
    }

    public function testDevelopmentSettings()
    {
        $bridgeService = new ViteBridge(
            'frooobaar!', // it does not matter what we pass here
            'foobar!!', // it does not matter what we pass here
            'anything', // it does not matter what we pass here
            'http://localhost:3000', // but the port must be reflected
        );
        $locator = $bridgeService->makePassiveEntryLocator(true);

        $assets = $locator->entry('main.js');
        Assert::same(
            '<script type="module" src="http://localhost:3000/@vite/client"></script>' .
            "\n" .
            '<script type="module" src="http://localhost:3000/main.js"></script>',
            (string)$assets,
            'Development settings',
        );
    }

    public function testBundleSettings()
    {
        $bridgeService = new ViteBridge(
            FIXTURES . '/manifest.official-example.json',
            TEMP . '/vite.default.php',
            'prefix', // This value must be reflected in the returned URLs
            'fooobaaarrr!', // this option is irrelevant in this case
        );
        $locator = $bridgeService->makePassiveEntryLocator(false);

        $assets = $locator->entry('main.js');
        Assert::same(
            '<script type="module" src="prefix/assets/main.4889e940.js"></script>' .
            "\n" .
            '<link rel="stylesheet" href="prefix/assets/main.b82dbe22.css" />',
            (string)$assets,
            'Prefix reflected in URLs',
        );
        $assets = $locator->entry('views/foo.js');
        Assert::same(
            '<script type="module" src="prefix/assets/foo.869aea0d.js"></script>' .
            "\n" .
            '<script type="module" src="prefix/assets/shared.83069a53.js"></script>',
            (string)$assets,
            'Prefix reflected in URLs',
        );
    }

    public function testEmptyPrefixSetting()
    {
        $bridgeService = new ViteBridge(
            FIXTURES . '/manifest.official-example.json',
            TEMP . '/vite.default.php',
            '',
        );
        $locator = $bridgeService->makePassiveEntryLocator(false);

        $assets = $locator->entry('main.js');
        Assert::same(
            '<script type="module" src="assets/main.4889e940.js"></script>' .
            "\n" .
            '<link rel="stylesheet" href="assets/main.b82dbe22.css" />',
            (string)$assets,
            'Prefix reflected in URLs',
        );
        $assets = $locator->entry('views/foo.js');
        Assert::same(
            '<script type="module" src="assets/foo.869aea0d.js"></script>' .
            "\n" .
            '<script type="module" src="assets/shared.83069a53.js"></script>',
            (string)$assets,
            'Prefix reflected in URLs',
        );
    }

    public function testSlashPrefixSetting()
    {
        $bridgeService = new ViteBridge(
            FIXTURES . '/manifest.official-example.json',
            TEMP . '/vite.default.php',
            '/',
        );
        $locator = $bridgeService->makePassiveEntryLocator(false);

        $assets = $locator->entry('main.js');
        Assert::same(
            '<script type="module" src="/assets/main.4889e940.js"></script>' .
            "\n" .
            '<link rel="stylesheet" href="/assets/main.b82dbe22.css" />',
            (string)$assets,
            'Prefix reflected in URLs',
        );
        $assets = $locator->entry('views/foo.js');
        Assert::same(
            '<script type="module" src="/assets/foo.869aea0d.js"></script>' .
            "\n" .
            '<script type="module" src="/assets/shared.83069a53.js"></script>',
            (string)$assets,
            'Prefix reflected in URLs',
        );
    }

    public function testCachePopulation()
    {
        $bridgeService = $this->getBridgeService();
        $bridgeService->populateCache();

        $filename = TEMP . '/vite.default.php';
        Assert::true(file_exists($filename), 'Cache file has not been written.');
        $map = require $filename;
        Assert::type('array', $map);
        Assert::same(3, sizeof($map));

        Assert::same(['main.js', 'views/foo.js', '_shared.83069a53.js'], array_keys($map));

        // now use invalid manifest to see reading from the cache file
        $bridgeService2 = new ViteBridge(
            FIXTURES . '/invalid.json',
            $filename,
            '/something',
        );
        $locator = $bridgeService2->makePassiveEntryLocator(false);

        $assets = $locator->entry('main.js');
        Assert::same(
            '<script type="module" src="/something/assets/main.4889e940.js"></script>' .
            "\n" .
            '<link rel="stylesheet" href="/something/assets/main.b82dbe22.css" />',
            (string)$assets,
            'Prefix reflected in URLs',
        );
        $assets = $locator->entry('views/foo.js');
        Assert::same(
            '<script type="module" src="/something/assets/foo.869aea0d.js"></script>' .
            "\n" .
            '<script type="module" src="/something/assets/shared.83069a53.js"></script>',
            (string)$assets,
            'Loaded from cache',
        );
    }
}

// run the test
(new _DefaultExampleViteBridgeTest)->run();
