<?php

namespace LaminasTest\XmlRpc\TestAsset;

use Laminas\XmlRpc\Client;

/**
 * related to Laminas-8478
 */
class TestClient extends Client
{
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
