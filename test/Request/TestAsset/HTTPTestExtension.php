<?php

namespace LaminasTest\XmlRpc\Request\TestAsset;

use Laminas\XmlRpc\Request\Http;

class HTTPTestExtension extends Http
{
    public function __construct($method = null, $params = null)
    {
        $this->method = $method;
        $this->params = (array) $params;
    }
}
