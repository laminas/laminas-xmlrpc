<?php

namespace Laminas\XmlRpc\Value;

use Brick\Math\BigInteger as BigIntegerMath;

class BigInteger extends Integer
{
    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = (string) BigIntegerMath::of($value);
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
