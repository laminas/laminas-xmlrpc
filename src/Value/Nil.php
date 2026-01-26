<?php

namespace Laminas\XmlRpc\Value;

use Override;

/**
 * @extends AbstractScalar<null>
 */
final class Nil extends AbstractScalar
{
    /**
     * Set the value of a nil native type
     */
    public function __construct()
    {
        $this->type  = self::XMLRPC_TYPE_NIL;
        $this->value = null;
    }

    /**
     * Return the value of this object, convert the XML-RPC native nill value into a PHP NULL
     */
    #[Override]
    public function getValue(): null
    {
        return null;
    }
}
