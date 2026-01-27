<?php

namespace Laminas\XmlRpc\Server\Exception;

use Laminas\XmlRpc\Exception;

/**
 * @psalm-suppress ClassMustBeFinal
 */
class RuntimeException extends Exception\RuntimeException implements ExceptionInterface
{
}
