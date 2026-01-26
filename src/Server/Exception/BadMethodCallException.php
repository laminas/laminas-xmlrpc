<?php

namespace Laminas\XmlRpc\Server\Exception;

use Laminas\XmlRpc\Exception;

final class BadMethodCallException extends Exception\BadMethodCallException implements ExceptionInterface
{
}
