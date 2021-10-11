<?php

namespace Laminas\XmlRpc\Value;

use Laminas\XmlRpc\Exception;

use const PHP_INT_MAX;

class Integer extends AbstractScalar
{
    /**
     * Set the value of an integer native type
     *
     * @param int $value
     * @throws Exception\ValueException
     */
    public function __construct($value)
    {
        if ($value > PHP_INT_MAX) {
            throw new Exception\ValueException('Overlong integer given');
        }

        $this->type  = self::XMLRPC_TYPE_INTEGER;
        $this->value = (int) $value;    // Make sure this value is integer
    }

    /**
     * Return the value of this object, convert the XML-RPC native integer value into a PHP integer
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }
}
