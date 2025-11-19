<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\TestAsset;

use Laminas\Diactoros\RequestFactory as DiactorosRequestFactory;
use Laminas\Diactoros\StreamFactory as DiactorosStreamFactory;
use Laminas\XmlRpc\Client;
use Laminas\XmlRpc\Client\ServerProxy;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * related to Laminas-8478
 */
final class TestClient extends Client
{
    public function __construct(
        string $server,
        ?HttpClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        parent::__construct(
            $server,
            $httpClient ?? new TestPsr18Client(),
            $requestFactory ?? new DiactorosRequestFactory(),
            $streamFactory ?? new DiactorosStreamFactory()
        );
    }

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
