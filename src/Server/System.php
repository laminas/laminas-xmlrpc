<?php

namespace Laminas\XmlRpc\Server;

use Exception;
use Laminas\XmlRpc\Fault;
use Laminas\XmlRpc\Request;
use Laminas\XmlRpc\Server;
use Laminas\XmlRpc\Server\Exception\InvalidArgumentException;

use function array_keys;
use function is_array;
use function var_export;

/**
 * XML-RPC system.* methods
 */
class System
{
    /** @var Server */
    protected $server;

    /**
     * Constructor
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * List all available XMLRPC methods
     *
     * Returns an array of methods.
     *
     * @return array
     */
    public function listMethods()
    {
        $table = $this->server->getDispatchTable()->getMethods();
        return array_keys($table);
    }

    /**
     * Display help message for an XMLRPC method
     *
     * @param string $method
     * @throws InvalidArgumentException
     * @return string
     */
    public function methodHelp($method)
    {
        $table = $this->server->getDispatchTable();
        if (! $table->hasMethod($method)) {
            throw new InvalidArgumentException('Method "' . $method . '" does not exist', 640);
        }

        return $table->getMethod($method)->getMethodHelp();
    }

    /**
     * Return a method signature
     *
     * @param string $method
     * @throws InvalidArgumentException
     * @return array
     */
    public function methodSignature($method)
    {
        $table = $this->server->getDispatchTable();
        if (! $table->hasMethod($method)) {
            throw new InvalidArgumentException('Method "' . $method . '" does not exist', 640);
        }
        $method = $table->getMethod($method)->toArray();
        return $method['prototypes'];
    }

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
     * @param  array $methods
     * @return array
     */
    public function multicall($methods)
    {
        $responses = [];
        foreach ($methods as $method) {
            $fault = false;
            if (! is_array($method)) {
                $fault = $this->server->fault('system.multicall expects each method to be a struct', 601);
            } elseif (! isset($method['methodName'])) {
                $fault = $this->server->fault('Missing methodName: ' . var_export($methods, 1), 602);
            } elseif (! isset($method['params'])) {
                $fault = $this->server->fault('Missing params', 603);
            } elseif (! is_array($method['params'])) {
                $fault = $this->server->fault('Params must be an array', 604);
            } else {
                if ('system.multicall' === $method['methodName']) {
                    // don't allow recursive calls to multicall
                    $fault = $this->server->fault('Recursive system.multicall forbidden', 605);
                }
            }

            if (! $fault) {
                try {
                    $request = new Request();
                    $request->setMethod($method['methodName']);
                    $request->setParams($method['params']);
                    $response = $this->server->handle($request);
                    if (
                        $response instanceof Fault
                        || $response->isFault()
                    ) {
                        $fault = $response;
                    } else {
                        $responses[] = $response->getReturnValue();
                    }
                } catch (Exception $e) {
                    $fault = $this->server->fault($e);
                }
            }

            if ($fault) {
                $responses[] = [
                    'faultCode'   => $fault->getCode(),
                    'faultString' => $fault->getMessage(),
                ];
            }
        }

        return $responses;
    }
}
