<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

use function implode;

/**
 * testFunction
 *
 * Function for use with xmlrpc server unit tests
 */
function testFunction(array $var1, string $var2 = 'optional'): string
{
    return $var2 . ': ' . implode(',', (array) $var1);
}

/**
 * testFunction2
 *
 * Function for use with xmlrpc server unit tests
 */
function testFunction2(): string
{
    return 'function2';
}
