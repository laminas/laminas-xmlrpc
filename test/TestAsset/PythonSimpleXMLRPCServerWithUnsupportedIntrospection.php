<?php
/**
 * @link      http://github.com/zendframework/zend-xmlrpc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\XmlRpc\TestAsset;

use Zend\XmlRpc\Client\ServerProxy;

/**
 * related to ZF-8478
 */
class PythonSimpleXMLRPCServerWithUnsupportedIntrospection extends ServerProxy
{
    public function __call($method, $args)
    {
        if ($method == 'methodSignature') {
            return 'signatures not supported';
        }
        return parent::__call($method, $args);
    }
}
