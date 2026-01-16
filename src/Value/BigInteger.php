<?php

namespace Laminas\XmlRpc\Value;

use Brick\Math\BigInteger as BigIntegerMath;

class BigInteger extends Integer
{
    public function __construct(string $value)
    {
        $this->value = (string) BigIntegerMath::of($value);
        $this->type  = self::XMLRPC_TYPE_I8;
    }

    /**
     * Return bigint value object
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
