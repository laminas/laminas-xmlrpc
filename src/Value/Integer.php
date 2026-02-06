<?php

namespace Laminas\XmlRpc\Value;

use Laminas\XmlRpc\Exception;
use Override;

use const PHP_INT_MAX;

/**
 * @extends AbstractScalar<int>
 */
class Integer extends AbstractScalar
{
    /**
     * Set the value of an integer native type
     *
     * @throws Exception\ValueException
     */
    public function __construct(int $value)
    {
        if ($value > PHP_INT_MAX) {
            throw new Exception\ValueException('Overlong integer given');
        }

        $this->type  = self::XMLRPC_TYPE_INTEGER;
        $this->value = $value;    // Make sure this value is integer
    }

    /**
     * Return the value of this object, convert the XML-RPC native integer value into a PHP integer
     *
     * @return int
     */
    #[Override]
    public function getValue(): string|int
    {
        return $this->value;
    }
}
