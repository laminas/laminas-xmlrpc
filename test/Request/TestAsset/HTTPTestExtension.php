<?php

declare(strict_types=1);

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
