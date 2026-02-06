<?php

namespace Laminas\XmlRpc\Value;

use Laminas\XmlRpc\AbstractValue;
use Override;

use function is_array;

/**
 * @extends AbstractScalar<array>
 */
abstract class AbstractCollection extends AbstractValue
{
    /**
     * Set the value of a collection type (array and struct) native types
     *
     * @param list<mixed> $value
     */
    public function __construct(array|object $value)
    {
        $values = (array) $value;   // Make sure that the value is an array
        foreach ($values as $key => $value) {
            // If the elements of the given array are not Laminas\XmlRpc\Value objects,
            // we need to convert them as such (using auto-detection from PHP value)
            if (! $value instanceof AbstractValue) {
                $value = static::getXmlRpcValue($value, self::AUTO_DETECT_TYPE);
            }
            $this->value[$key] = $value;
        }

        if (! isset($this->value) || ! is_array($this->value)) {
            $this->value = [];
        }
    }

    /**
     * Return the value of this object, convert the XML-RPC native collection values into a PHP array
     */
    #[Override]
    public function getValue(): array
    {
        $values = $this->value;
        foreach ($values as $key => $value) {
            $values[$key] = $value->getValue();
        }
        return $values;
    }
}
