<?php

namespace Laminas\XmlRpc\Value;

use function ini_get;
use function rtrim;
use function sprintf;

class Double extends AbstractScalar
{
    /**
     * Set the value of a double native type
     */
    public function __construct(float $value)
    {
        $this->type   = self::XMLRPC_TYPE_DOUBLE;
        $precision    = (int) ini_get('precision');
        $formatString = '%1.' . $precision . 'F';
        $this->value  = rtrim(sprintf($formatString, (float) $value), '0');
    }

    /**
     * Return the value of this object, convert the XML-RPC native double value into a PHP float
     */
    public function getValue(): float
    {
        return (float) $this->value;
    }
}
