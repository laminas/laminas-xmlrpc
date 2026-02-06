<?php

namespace Laminas\XmlRpc\Client;

interface ProxyInterface
{
    /**
     * Get the next successive namespace
     */
    public function __get(string $namespace): ProxyInterface;

    /**
     * Call a method in this namespace.
     *
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): mixed;
}
