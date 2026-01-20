<?php

namespace Laminas\XmlRpc\Value;

use function ini_get;
use function is_float;
use function rtrim;
use function sprintf;

/**
 * @extends AbstractScalar<float>
 */
final class Double extends AbstractScalar
{
    /**
     * Set the value of a double native type
     */
    public function __construct(float|string $value)
    {
        $this->type = self::XMLRPC_TYPE_DOUBLE;

        if (is_float($value)) {
            $this->value = $value;
        } else {
            $precision    = (int) ini_get('precision');
            $formatString = '%1.' . $precision . 'F';
            $this->value  = (float) rtrim(sprintf($formatString, $value), '0');
        }
    }

    /**
     * Return the value of this object, convert the XML-RPC native double value into a PHP float
     */
    public function getValue(): float
    {
        return $this->value;
    }
}
