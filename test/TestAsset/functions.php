<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

use function implode;

/**
 * testFunction
 *
 * Function for use with xmlrpc server unit tests
 *
 * @param array $var1
 * @param string $var2
 * @return string
 */
function testFunction($var1, $var2 = 'optional')
{
    return $var2 . ': ' . implode(',', (array) $var1);
}

/**
 * testFunction2
 *
 * Function for use with xmlrpc server unit tests
 *
 * @return string
 */
function testFunction2()
{
    return 'function2';
}
