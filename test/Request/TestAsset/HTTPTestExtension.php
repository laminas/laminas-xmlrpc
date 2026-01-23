<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\Request\TestAsset;

use Laminas\XmlRpc\Request\Http;

class HTTPTestExtension extends Http
{
    public function __construct(mixed $method = null, mixed $params = null)
    {
        $this->method = $method;
        $this->params = (array) $params;
    }
}
