<?php

namespace Laminas\XmlRpc\Value;

use Laminas\Math\BigInteger\BigInteger as BigIntegerMath;

class BigInteger extends Integer
{
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = BigIntegerMath::factory()->init($value, 10);
        $this->type  = self::XMLRPC_TYPE_I8;
    }

    /**
     * Return bigint value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
