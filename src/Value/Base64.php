<?php

namespace Laminas\XmlRpc\Value;

use function base64_decode;
use function base64_encode;

/**
 * @extends AbstractScalar<string>
 */
final class Base64 extends AbstractScalar
{
    /**
     * Set the value of a base64 native type
     * We keep this value in base64 encoding
     *
     * @param bool $alreadyEncoded If set, it means that the given string is already base64 encoded
     */
    public function __construct(string $value, bool $alreadyEncoded = false)
    {
        $this->type = self::XMLRPC_TYPE_BASE64;

        if (! $alreadyEncoded) {
            $value = base64_encode($value);     // We encode it in base64
        }
        $this->value = $value;
    }

    /**
     * Return the value of this object, convert the XML-RPC native base64 value into a PHP string
     * We return this value decoded (a normal string)
     */
    public function getValue(): string
    {
        return base64_decode($this->value);
    }
}
