<?php
/**
 * @link      http://github.com/zendframework/zend-xmlrpc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\XmlRpc\Request\TestAsset;

use Zend\XmlRpc\Request\Http;

class HTTPTestExtension extends Http
{
    public function __construct($method = null, $params = null)
    {
        $this->method = $method;
        $this->params = (array) $params;
    }
}
