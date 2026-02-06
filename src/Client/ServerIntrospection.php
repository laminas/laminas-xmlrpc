<?php

namespace Laminas\XmlRpc\Client;

use Laminas\XmlRpc\Client as XMLRPCClient;
use Laminas\XmlRpc\Client\IntrospectInterface;
use Override;

use function count;
use function gettype;
use function is_array;

/**
 * Wraps the XML-RPC system.* introspection methods
 */
final class ServerIntrospection implements IntrospectInterface
{
    private readonly ProxyInterface $system;

    public function __construct(XMLRPCClient $client)
    {
        $this->system = $client->getProxy('system');
    }

    /**
     * Returns the signature for each method on the server,
     * autodetecting whether system.multicall() is supported and
     * using it if so.
     *
     * @return (array|string)[]
     * @psalm-return array<int, array<int, mixed>|string>
     */
    #[Override]
    public function getSignatureForEachMethod(): array
    {
        $methods = $this->listMethods();

        try {
            $signatures = $this->getSignatureForEachMethodByMulticall($methods);
        } catch (Exception\FaultException) {
            // degrade to looping
        }

        if (empty($signatures)) {
            $signatures = $this->getSignatureForEachMethodByLooping($methods);
        }

        return $signatures;
    }

    /**
     * Attempt to get the method signatures in one request via system.multicall().
     * This is a boxcar feature of XML-RPC and is found on fewer servers.  However,
     * can significantly improve performance if present.
     *
     * @param array<int, string>|null $methods
     * @throws Exception\IntrospectException
     * @return array<string, array<int, mixed>>
     */
    #[Override]
    public function getSignatureForEachMethodByMulticall(array|null $methods = null): array
    {
        if ($methods === null) {
            $methods = $this->listMethods();
        }

        $multicallParams = [];
        foreach ($methods as $method) {
            $multicallParams[] = [
                'methodName' => 'system.methodSignature',
                'params'     => [$method],
            ];
        }

        $serverSignatures = $this->system->multicall($multicallParams);

        if (! is_array($serverSignatures)) {
            $type  = gettype($serverSignatures);
            $error = "Multicall return is malformed.  Expected array, got $type";
            throw new Exception\IntrospectException($error);
        }

        if (count($serverSignatures) !== count($methods)) {
            $error = 'Bad number of signatures received from multicall';
            throw new Exception\IntrospectException($error);
        }

        // Create a new signatures array with the methods name as keys and the signature as value
        $signatures = [];
        foreach ($serverSignatures as $i => $signature) {
            $signatures[$methods[$i]] = $signature;
        }

        return $signatures;
    }

    /**
     * Get the method signatures for every method by
     * successively calling system.methodSignature
     *
     * @param array<int, string>|null $methods
     * @return array{string, array{array{'returnType': string, 'parameters': array}}}
     */
    #[Override]
    public function getSignatureForEachMethodByLooping(array|null $methods = null): array
    {
        if ($methods === null) {
            $methods = $this->listMethods();
        }

        $signatures = [];
        foreach ($methods as $method) {
            $signatures[$method] = $this->getMethodSignature($method);
        }

        return $signatures;
    }

    /**
     * Call system.methodSignature() for the given method
     *
     * @throws Exception\IntrospectException
     * @return array{array{'returnType': string, 'parameters': array}}
     */
    #[Override]
    public function getMethodSignature(string $method): array
    {
        $signature = $this->system->methodSignature($method);
        if (! is_array($signature)) {
            $error = 'Invalid signature for method "' . $method . '"';
            throw new Exception\IntrospectException($error);
        }
        return $signature;
    }

    /**
     * Call system.listMethods()
     *
     * @return array<int, string>
     */
    #[Override]
    public function listMethods(): array
    {
        return $this->system->listMethods();
    }
}
