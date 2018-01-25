<?php
/**
 * @see       https://github.com/zendframework/zend-xmlrpc for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-xmlrpc/blob/master/LICENSE.md New BSD License
 */

/**
 * @todo This file may be removed once we drop support for PHP 5.
 */
if (! class_exists(PHPUnit\Framework\ExpectationFailedException::class)) {
    class_alias(
        PHPUnit_Framework_ExpectationFailedException::class,
        PHPUnit\Framework\ExpectationFailedException::class,
        true
    );
}
