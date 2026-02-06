<?php

namespace Laminas\XmlRpc\Client;

interface IntrospectInterface
{
    /**
     * Returns the signature for each method on the server,
     * autodetecting whether system.multicall() is supported and
     * using it if so.
     *
     * @return array<int, array<int, mixed>|string>
     */
    public function getSignatureForEachMethod(): array;

    /**
     * Attempt to get the method signatures in one request via system.multicall().
     * This is a boxcar feature of XML-RPC and is found on fewer servers.  However,
     * can significantly improve performance if present.
     *
     * @param array<int, string>|null $methods
     * @throws Exception\IntrospectException
     * @return array<string, array<int, mixed>>
     */
    public function getSignatureForEachMethodByMulticall(array|null $methods = null): array;

    /**
     * Get the method signatures for every method by
     * successively calling system.methodSignature
     *
     * @param array<int, string>|null $methods
     * @return array{string, array{array{'returnType': string, 'parameters': array}}}
     */
    public function getSignatureForEachMethodByLooping(array|null $methods = null): array;

    /**
     * Call system.methodSignature() for the given method
     *
     * @throws Exception\IntrospectException
     * @return array{array{'returnType': string, 'parameters': array}}
     */
    public function getMethodSignature(string $method): array;

    /**
     * Call system.listMethods()
     *
     * @return array<int, string>
     */
    public function listMethods(): array;
}
