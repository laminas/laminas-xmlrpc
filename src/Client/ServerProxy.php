<?php

namespace Laminas\XmlRpc\Client;

use Laminas\XmlRpc\Client as XMLRPCClient;
use Override;

use function ltrim;

/**
 * The namespace decorator enables object chaining to permit
 * calling XML-RPC namespaced functions like "foo.bar.baz()"
 * as "$remote->foo->bar->baz()".
 */
final class ServerProxy implements ProxyInterface
{
    /** @var array<string, ProxyInterface> */
    private array $cache = [];

    public function __construct(private XMLRPCClient $client, private string $namespace = '')
    {
    }

    /**
     * Get the next successive namespace
     */
    #[Override]
    public function __get(string $namespace): ProxyInterface
    {
        $namespace = ltrim("$this->namespace.$namespace", '.');
        if (! isset($this->cache[$namespace])) {
            $this->cache[$namespace] = new $this($this->client, $namespace);
        }
        return $this->cache[$namespace];
    }

    /**
     * Call a method in this namespace.
     *
     * @param array<int, mixed> $args
     */
    #[Override]
    public function __call(string $method, array $args): mixed
    {
        $method = ltrim("{$this->namespace}.{$method}", '.');
        return $this->client->call($method, $args);
    }
}
