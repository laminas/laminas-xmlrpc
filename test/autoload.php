<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
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
