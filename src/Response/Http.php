<?php

namespace Laminas\XmlRpc\Response;

use Laminas\XmlRpc\Response as XmlRpcResponse;

use function header;
use function headers_sent;
use function strtolower;

/**
 * HTTP response
 */
class Http extends XmlRpcResponse
{
    /**
     * Override __toString() to send HTTP Content-Type header
     *
     * @return string
     */
    public function __toString()
    {
        if (! headers_sent()) {
            header('Content-Type: text/xml; charset=' . strtolower($this->getEncoding()));
        }

        return parent::__toString();
    }
}
