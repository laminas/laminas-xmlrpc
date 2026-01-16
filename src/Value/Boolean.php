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
    public function __construct(bool $value)
    {
        $this->type = self::XMLRPC_TYPE_BOOLEAN;
        // Make sure the value is boolean and then convert it into an integer
        // The double conversion is because a bug in the LaminasOptimizer in PHP version 5.0.4
        $this->value = (int) filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Return the value of this object, convert the XML-RPC native boolean value into a PHP boolean
     */
    public function getValue(): bool
    {
        return (bool) $this->value;
    }
}
