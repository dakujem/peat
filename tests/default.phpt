<?php

declare(strict_types=1);


namespace Dakujem\Trest;

require_once __DIR__ . '/common.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * This test tests the capabilities on the official Vite backend integration example.
 *
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _DefaultExampleTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testValueMapping()
    {
        Assert::same(true, false, 'Got it wrong pal.');

        $bridgeService = new ViteBridge(
            manifestFile: ROOT_DIR . '/public/my-js-widget/manifest.json',
            cacheFile: TEMP_DIR . '/vite.php',   // can be any writable file
            assetPath: 'my-js-widget',   // relative path from /public to the dir where the manifest is located
            devServerUrl: 'http://localhost:5173',
        );

    }
}

// run the test
(new _DefaultExampleTest)->run();
