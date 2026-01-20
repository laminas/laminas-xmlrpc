<?php

namespace Laminas\XmlRpc\Client;

use Laminas\XmlRpc\Client as XMLRPCClient;

use function ltrim;

/**
 * The namespace decorator enables object chaining to permit
 * calling XML-RPC namespaced functions like "foo.bar.baz()"
 * as "$remote->foo->bar->baz()".
 */
class ServerProxy
{
    /** @var array<string, ServerProxy> */
    private array $cache = [];

    public function __construct(private XMLRPCClient $client, private string $namespace = '')
    {
    }

    /**
     * Get the next successive namespace
     */
    public function __get(string $namespace): ServerProxy
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
    public function __call(string $method, array $args): mixed
    {
        $method = ltrim("{$this->namespace}.{$method}", '.');
        return $this->client->call($method, $args);
    }
}
