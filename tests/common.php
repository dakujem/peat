<?php

declare(strict_types=1);

namespace Dakujem\Trest;

define('ROOT', __DIR__);
define('FIXTURES', __DIR__.'/fixtures');
define('TEMP', __DIR__ . '/workdir');

require_once __DIR__ . '/../vendor/autoload.php';

use Tester\Environment;
use Tracy\Debugger;

// tester
Environment::setup();

// debugging - when not run via a browser
if (function_exists('getallheaders') && !empty(getallheaders())) {
    Debugger::$strictMode = true;
    Debugger::enable();
    Debugger::$maxDepth = 10;
    Debugger::$maxLen = 500;
}


// dump shortcut
function dump($var, $return = false)
{
    return Debugger::dump($var, $return);
}
