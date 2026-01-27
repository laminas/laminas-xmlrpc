<?php

namespace Laminas\XmlRpc\Client\Exception;

use Laminas\XmlRpc\Exception;

/**
 * Thrown by Laminas\XmlRpc\Client when an XML-RPC fault response is returned.
 *
 * @psalm-suppress ClassMustBeFinal
 */
class FaultException extends Exception\BadMethodCallException implements ExceptionInterface
{
}
