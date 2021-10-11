<?php

namespace Laminas\XmlRpc\Value;

use function ini_get;
use function rtrim;
use function sprintf;

class Double extends AbstractScalar
{
    /**
     * Set the value of a double native type
     *
     * @param float $value
     */
    public function __construct($value)
    {
        $this->type   = self::XMLRPC_TYPE_DOUBLE;
        $precision    = (int) ini_get('precision');
        $formatString = '%1.' . $precision . 'F';
        $this->value  = rtrim(sprintf($formatString, (float) $value), '0');
    }

    /**
     * Return the value of this object, convert the XML-RPC native double value into a PHP float
     *
     * @return float
     */
    public function getValue()
    {
        return (float) $this->value;
    }
}
