<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc\TestAsset;

use Laminas\XmlRpc\Client;
use Laminas\XmlRpc\Client\ServerProxy;

/**
 * related to Laminas-8478
 */
class TestClient extends Client
{
    /**
     * @param string $namespace
     * @return ServerProxy
     */
    public function getProxy($namespace = '')
    {
        if (empty($this->proxyCache[$namespace])) {
            $this->proxyCache[$namespace] = new PythonSimpleXMLRPCServerWithUnsupportedIntrospection(
                $this,
                $namespace
            );
        }
        return parent::getProxy($namespace);
    }
}
