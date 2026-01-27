<?php

namespace Laminas\XmlRpc\Server\Exception;

use Laminas\XmlRpc\Exception;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class InvalidArgumentException extends Exception\InvalidArgumentException implements ExceptionInterface
{
}
