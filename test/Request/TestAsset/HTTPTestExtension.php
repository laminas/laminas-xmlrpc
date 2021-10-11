<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc\Request\TestAsset;

use Laminas\XmlRpc\Request\Http;

class HTTPTestExtension extends Http
{
    /**
     * @param mixed $method
     * @param mixed $params
     */
    public function __construct($method = null, $params = null)
    {
        $this->method = $method;
        $this->params = (array) $params;
    }
}
