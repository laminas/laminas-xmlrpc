<?php

namespace Laminas\XmlRpc\Server;

use Laminas\XmlRpc\Server\Exception\InvalidArgumentException;

interface SystemInterface
{
    /**
     * List all available XMLRPC methods
     *
     * Returns an array of methods.
     *
     * @return array<int, string>
     */
    public function listMethods(): array;

    /**
     * Display help message for an XMLRPC method
     *
     * @param string $method Is required for PHPUnit
     * @throws InvalidArgumentException
     * @return string Type Is required for PHPUnit
     */
    public function methodHelp(string $method): string;

    /**
     * Return a method signature
     *
     * @throws InvalidArgumentException
     * @return array<int, array<string, string|array>>
     */
    public function methodSignature(string $method): array;

    /**
     * Multicall - boxcar feature of XML-RPC for calling multiple methods
     * in a single request.
     *
     * Expects an array of structs representing method calls, each element
     * having the keys:
     * - methodName
     * - params
     *
     * Returns an array of responses, one for each method called, with the value
     * returned by the method. If an error occurs for a given method, returns a
     * struct with a fault response.
     *
     * @see http://www.xmlrpc.com/discuss/msgReader$1208
     *
     * @param array<string, mixed> $methods
     *
     * @return (array|mixed)[]
     *
     * @psalm-return list<array{faultCode: mixed, faultString: mixed}|mixed>
     */
    public function multicall(array $methods): array;
}
