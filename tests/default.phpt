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
    }
}

// run the test
(new _DefaultExampleTest)->run();
