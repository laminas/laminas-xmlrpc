<?php

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
