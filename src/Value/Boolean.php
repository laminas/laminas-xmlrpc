<?php

namespace Laminas\XmlRpc\Value;

use function filter_var;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * @extends AbstractScalar<bool>
 */
final class Boolean extends AbstractScalar
{
    /**
     * Set the value of a boolean native type
     * We hold the boolean type as an integer (0 or 1)
     */
    public function __construct(bool|string|int $value)
    {
        $this->type  = self::XMLRPC_TYPE_BOOLEAN;
        $this->value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Return the value of this object, convert the XML-RPC native boolean value into a PHP boolean
     */
    #[\Override]
    public function getValue(): bool
    {
        return $this->value;
    }
}
