<?php

namespace Laminas\XmlRpc;

use Laminas\Server\ClientInterface as ServerClient;
use Laminas\XmlRpc\Client\Exception\FaultException;
use Laminas\XmlRpc\Client\Exception\HttpException;
use Laminas\XmlRpc\Client\Exception\InvalidArgumentException;
use Laminas\XmlRpc\Client\Exception\RuntimeException;
use Laminas\XmlRpc\Client\ServerIntrospection;
use Laminas\XmlRpc\Client\ServerProxy;
use Laminas\XmlRpc\Exception\ExceptionInterface;
use Laminas\XmlRpc\Exception\ValueException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function assert;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function str_starts_with;
use function trim;

/**
 * An XML-RPC client implementation
 */
final class Client implements ServerClient
{
    /**
     * Introspection object
     *
     * @var ServerIntrospection
     */
    protected $introspector;

    /**
     * Request of the last method call
     *
     * @var Request|null
     */
    protected $lastRequest;

    /**
     * Response received from the last method call
     *
     * @var Response|null
     */
    protected $lastResponse;

    /**
     * Proxy object for more convenient method calls
     *
     * @var array<string, ServerProxy>
     */
    protected $proxyCache = [];

    /**
     * Flag for skipping system lookup
     *
     * @var bool
     */
    protected $skipSystemLookup = false;

    private const USERAGENT = 'Laminas_XmlRpc_Client';

    /**
     * Create a new XML-RPC client to a remote server
     *
     * @param  string $serverAddress      Full address of the XML-RPC service
     *                             (e.g. http://time.xmlrpc.com/RPC2)
     * @param  HttpClientInterface $httpClient HTTP Client to use for requests
     * @param RequestFactoryInterface $requestFactory PSR17 request factory
     * @param StreamFactoryInterface $streamFactory PSR17 stream factory
     */
    public function __construct(
        private readonly string $serverAddress,
        private readonly HttpClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {
        $this->introspector = new ServerIntrospection($this);
    }

    /**
     * Sets the object used to introspect remote servers
     *
     * @return ServerIntrospection
     */
    public function setIntrospector(ServerIntrospection $introspector)
    {
        return $this->introspector = $introspector;
    }

    /**
     * Gets the introspection object.
     *
     * @return ServerIntrospection
     */
    public function getIntrospector()
    {
        return $this->introspector;
    }

    /**
     * The request of the last method call
     *
     * @return Request|null
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * The response received from the last method call
     *
     * @return Response|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Returns a proxy object for more convenient method calls
     *
     * @param string $namespace  Namespace to proxy or empty string for none
     * @return ServerProxy
     */
    public function getProxy($namespace = '')
    {
        if (empty($this->proxyCache[$namespace])) {
            $proxy                        = new ServerProxy($this, $namespace);
            $this->proxyCache[$namespace] = $proxy;
        }
        return $this->proxyCache[$namespace];
    }

    /**
     * Set skip system lookup flag
     *
     * @param bool $flag
     * @return Client
     */
    public function setSkipSystemLookup($flag = true)
    {
        $this->skipSystemLookup = (bool) $flag;
        return $this;
    }

    /**
     * Skip system lookup when determining if parameter should be array or struct?
     *
     * @return bool
     */
    public function skipSystemLookup()
    {
        return $this->skipSystemLookup;
    }

    /**
     * Perform an XML-RPC request and return a response.
     *
     * You may optionally pass a bitmask of LIBXML options via the
     * $libXmlOptions parameter; as an example, you might use LIBXML_PARSEHUGE.
     * See https://www.php.net/manual/en/libxml.constants.php for a full list.
     *
     * @param Request $request
     * @param int $libXmlOptions Bitmask of LIBXML options to use for XML * operations
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws HttpException
     * @throws ValueException
     * @return void
     */
    public function doRequest($request, int $libXmlOptions = 0)
    {
        $this->lastRequest = $request;

        $xml = $this->lastRequest->__toString();

        // Build PSR-7 request
        $psrRequest = $this->requestFactory
            ->createRequest('POST', $this->serverAddress)
            ->withHeader('Content-Type', 'text/xml; charset=utf-8')
            ->withHeader('Accept', 'text/xml')
            ->withHeader('User-Agent', self::USERAGENT);

        $stream     = $this->streamFactory->createStream($xml);
        $psrRequest = $psrRequest->withBody($stream);

        try {
            $psrResponse = $this->httpClient->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            // Wrap the client-specific exception in your existing HttpException
            throw new HttpException($e->getMessage(), 0, $e);
        }

        $statusCode = $psrResponse->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new HttpException(
                $psrResponse->getReasonPhrase(),
                $statusCode
            );
        }

        $body = $psrResponse->getBody()->__toString();

        $response = new Response();

        $this->lastResponse = $response;
        $this->lastResponse->loadXml(trim($body), $libXmlOptions);
    }

    /**
     * Send an XML-RPC request to the service (for a specific method)
     *
     * @param  string $method Name of the method we want to call
     * @param  array $params Array of parameters for the method
     * @return mixed
     * @throws FaultException
     */
    public function call($method, $params = [])
    {
        if (! $this->skipSystemLookup() && (! str_starts_with($method, 'system.'))) {
            // Ensure empty array/struct params are cast correctly
            // If system.* methods are not available, bypass. (Laminas-2978)
            $success = true;
            try {
                $signatures = $this->getIntrospector()->getMethodSignature($method);
            } catch (ExceptionInterface) {
                $success = false;
            }
            if ($success) {
                $validTypes = [
                    AbstractValue::XMLRPC_TYPE_ARRAY,
                    AbstractValue::XMLRPC_TYPE_BASE64,
                    AbstractValue::XMLRPC_TYPE_BOOLEAN,
                    AbstractValue::XMLRPC_TYPE_DATETIME,
                    AbstractValue::XMLRPC_TYPE_DOUBLE,
                    AbstractValue::XMLRPC_TYPE_I4,
                    AbstractValue::XMLRPC_TYPE_INTEGER,
                    AbstractValue::XMLRPC_TYPE_NIL,
                    AbstractValue::XMLRPC_TYPE_STRING,
                    AbstractValue::XMLRPC_TYPE_STRUCT,
                ];

                if (! is_array($params)) {
                    $params = [$params];
                }
                foreach ($params as $key => $param) {
                    if ($param instanceof AbstractValue) {
                        continue;
                    }

                    assert(isset($signatures));

                    if (count($signatures) > 1) {
                        $type = AbstractValue::getXmlRpcTypeByValue($param);
                        foreach ($signatures as $signature) {
                            if (! is_array($signature)) {
                                continue;
                            }
                            if (isset($signature['parameters'][$key])) {
                                if ($signature['parameters'][$key] === $type) {
                                    break;
                                }
                            }
                        }
                    } elseif (isset($signatures[0]['parameters'][$key])) {
                        $type = $signatures[0]['parameters'][$key];
                    } else {
                        $type = null;
                    }

                    if (! is_string($type) || ! in_array($type, $validTypes)) {
                        $type = AbstractValue::AUTO_DETECT_TYPE;
                    }

                    /** @psalm-var AbstractValue::XMLRPC_TYPE_* $type */

                    $params[$key] = AbstractValue::getXmlRpcValue($param, $type);
                }
            }
        }

        $request = $this->createRequest($method, $params);

        $this->doRequest($request);

        assert($this->lastResponse !== null);

        if ($this->lastResponse->isFault()) {
            $fault = $this->lastResponse->getFault();
            /**
             * Exception thrown when an XML-RPC fault is returned
             */
            throw new FaultException(
                $fault->getMessage(),
                $fault->getCode()
            );
        }

        return $this->lastResponse->getReturnValue();
    }

    /**
     * Create request object
     *
     * @param string|null $method
     * @param array|null $params
     * @return Request
     */
    protected function createRequest($method = null, $params = null)
    {
        return new Request($method, $params);
    }
}
