<?php

namespace Laminas\XmlRpc\Server\Exception;

use Laminas\XmlRpc\Exception;

class BadMethodCallException extends Exception\BadMethodCallException implements ExceptionInterface
{
}
