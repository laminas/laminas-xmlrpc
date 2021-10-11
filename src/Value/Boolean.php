<?php

namespace Laminas\XmlRpc\Value;

class Boolean extends AbstractScalar
{
    /**
     * Set the value of a boolean native type
     * We hold the boolean type as an integer (0 or 1)
     *
     * @param bool $value
     */
    public function __construct($value)
    {
        $this->type = self::XMLRPC_TYPE_BOOLEAN;
        // Make sure the value is boolean and then convert it into an integer
        // The double conversion is because a bug in the LaminasOptimizer in PHP version 5.0.4
        $this->value = (int) (bool) $value;
    }

    /**
     * Return the value of this object, convert the XML-RPC native boolean value into a PHP boolean
     *
     * @return bool
     */
    public function getValue()
    {
        return (bool) $this->value;
    }
}
