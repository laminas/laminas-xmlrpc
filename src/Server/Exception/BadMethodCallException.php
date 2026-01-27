<?php

namespace Laminas\XmlRpc\Server\Exception;

use Laminas\XmlRpc\Exception;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class BadMethodCallException extends Exception\BadMethodCallException implements ExceptionInterface
{
}
