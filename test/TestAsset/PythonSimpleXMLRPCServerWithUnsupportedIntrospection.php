<?php

namespace LaminasTest\XmlRpc\TestAsset;

use Laminas\XmlRpc\Client\ServerProxy;

/**
 * related to Laminas-8478
 */
class PythonSimpleXMLRPCServerWithUnsupportedIntrospection extends ServerProxy
{
    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($method === 'methodSignature') {
            return 'signatures not supported';
        }
        return parent::__call($method, $args);
    }
}
