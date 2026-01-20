<?php

namespace Laminas\XmlRpc\Value;

use function is_array;

class ArrayValue extends AbstractCollection
{
    /**
     * Set the value of an array native type
     *
     * @param list<mixed> $value
     */
    public function __construct(array|object $value)
    {
        $this->type = self::XMLRPC_TYPE_ARRAY;
        parent::__construct($value);
    }

    /**
     * Generate the XML code that represent an array native MXL-RPC value
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function generate(): void
    {
        $generator = static::getGenerator();
        $generator
            ->openElement('value')
            ->openElement('array')
            ->openElement('data');

        if (is_array($this->value)) {
            foreach ($this->value as $val) {
                $val->generateXml();
            }
        }

        $generator
            ->closeElement('data')
            ->closeElement('array')
            ->closeElement('value');
    }
}
