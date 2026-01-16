<?php

namespace Laminas\XmlRpc\Value;

class Text extends AbstractScalar
{
    /**
     * Set the value of a string native type
     */
    public function __construct(string $value)
    {
        $this->type = self::XMLRPC_TYPE_STRING;

        // Make sure this value is string and all XML characters are encoded
        $this->value = (string) $value;
    }

    /**
     * Return the value of this object, convert the XML-RPC native string value into a PHP string
     */
    public function getValue(): string
    {
        return (string) $this->value;
    }
}
