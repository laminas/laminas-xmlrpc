<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

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
