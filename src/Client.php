<?php

namespace Laminas\XmlRpc;

use Laminas\Http;
use Laminas\Server\Client as ServerClient;

/**
 * An XML-RPC client implementation
 */
class Client implements ServerClient
{
    /**
     * Full address of the XML-RPC service
     * @var string
     * @example http://time.xmlrpc.com/RPC2
     */
    protected $serverAddress;

    /**
     * HTTP Client to use for requests
     * @var \Laminas\Http\Client
     */
    protected $httpClient = null;

    /**
     * Introspection object
     * @var \Laminas\XmlRpc\Client\ServerIntrospection
     */
    protected $introspector = null;

    /**
     * Request of the last method call
     * @var \Laminas\XmlRpc\Request
     */
    protected $lastRequest = null;

    /**
     * Response received from the last method call
     * @var \Laminas\XmlRpc\Response
     */
    protected $lastResponse = null;

    /**
     * Proxy object for more convenient method calls
     * @var array of Laminas\XmlRpc\Client\ServerProxy
     */
    protected $proxyCache = [];

    /**
     * Flag for skipping system lookup
     * @var bool
     */
    protected $skipSystemLookup = false;

    /**
     * Create a new XML-RPC client to a remote server
     *
     * @param  string $server      Full address of the XML-RPC service
     *                             (e.g. http://time.xmlrpc.com/RPC2)
     * @param  \Laminas\Http\Client $httpClient HTTP Client to use for requests
     */
    public function __construct($server, Http\Client $httpClient = null)
    {
        if ($httpClient === null) {
            $this->httpClient = new Http\Client();
        } else {
            $this->httpClient = $httpClient;
        }

        $this->introspector  = new Client\ServerIntrospection($this);
        $this->serverAddress = $server;
    }

    /**
     * Sets the HTTP client object to use for connecting the XML-RPC server.
     *
     * @param  \Laminas\Http\Client $httpClient
     * @return \Laminas\Http\Client
     */
    public function setHttpClient(Http\Client $httpClient)
    {
        return $this->httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return \Laminas\Http\Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Sets the object used to introspect remote servers
     *
     * @param  \Laminas\XmlRpc\Client\ServerIntrospection
     * @return \Laminas\XmlRpc\Client\ServerIntrospection
     */
    public function setIntrospector(Client\ServerIntrospection $introspector)
    {
        return $this->introspector = $introspector;
    }

    /**
     * Gets the introspection object.
     *
     * @return \Laminas\XmlRpc\Client\ServerIntrospection
     */
    public function getIntrospector()
    {
        return $this->introspector;
    }

    /**
     * The request of the last method call
     *
     * @return \Laminas\XmlRpc\Request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * The response received from the last method call
     *
     * @return \Laminas\XmlRpc\Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Returns a proxy object for more convenient method calls
     *
     * @param string $namespace  Namespace to proxy or empty string for none
     * @return \Laminas\XmlRpc\Client\ServerProxy
     */
    public function getProxy($namespace = '')
    {
        if (empty($this->proxyCache[$namespace])) {
            $proxy = new Client\ServerProxy($this, $namespace);
            $this->proxyCache[$namespace] = $proxy;
        }
        return $this->proxyCache[$namespace];
    }

    /**
     * Set skip system lookup flag
     *
     * @param  bool $flag
     * @return \Laminas\XmlRpc\Client
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
     * @param \Laminas\XmlRpc\Request       $request
     * @param null|\Laminas\XmlRpc\Response $response
     *
     * @throws \Laminas\Http\Exception\InvalidArgumentException
     * @throws \Laminas\Http\Client\Exception\RuntimeException
     * @throws \Laminas\XmlRpc\Client\Exception\HttpException
     * @throws \Laminas\XmlRpc\Exception\ValueException
     *
     * @return void
     */
    public function doRequest($request, $response = null)
    {
        $this->lastRequest = $request;

        if (PHP_VERSION_ID < 50600) {
            iconv_set_encoding('input_encoding', 'UTF-8');
            iconv_set_encoding('output_encoding', 'UTF-8');
            iconv_set_encoding('internal_encoding', 'UTF-8');
        } else {
            ini_set('default_charset', 'UTF-8');
        }

        $http        = $this->getHttpClient();
        $httpRequest = $http->getRequest();
        if ($httpRequest->getUriString() === null) {
            $http->setUri($this->serverAddress);
        }

        $headers = $httpRequest->getHeaders();

        if (! $headers->has('Content-Type')) {
            $headers->addHeaderLine('Content-Type', 'text/xml; charset=utf-8');
        }

        if (! $headers->has('Accept')) {
            $headers->addHeaderLine('Accept', 'text/xml');
        }

        if (! $headers->has('user-agent')) {
            $headers->addHeaderLine('user-agent', 'Laminas_XmlRpc_Client');
        }

        $xml = $this->lastRequest->__toString();
        $http->setRawBody($xml);
        $httpResponse = $http->setMethod('POST')->send();

        if (! $httpResponse->isSuccess()) {
            /**
             * Exception thrown when an HTTP error occurs
             */
            throw new Client\Exception\HttpException(
                $httpResponse->getReasonPhrase(),
                $httpResponse->getStatusCode()
            );
        }

        if ($response === null) {
            $response = new Response();
        }

        $this->lastResponse = $response;
        $this->lastResponse->loadXml(trim($httpResponse->getBody()));
    }

    /**
     * Send an XML-RPC request to the service (for a specific method)
     *
     * @param  string $method Name of the method we want to call
     * @param  array $params Array of parameters for the method
     * @return mixed
     * @throws \Laminas\XmlRpc\Client\Exception\FaultException
     */
    public function call($method, $params = [])
    {
        if (! $this->skipSystemLookup() && ('system.' != substr($method, 0, 7))) {
            // Ensure empty array/struct params are cast correctly
            // If system.* methods are not available, bypass. (Laminas-2978)
            $success = true;
            try {
                $signatures = $this->getIntrospector()->getMethodSignature($method);
            } catch (\Laminas\XmlRpc\Exception\ExceptionInterface $e) {
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

                    if (count($signatures) > 1) {
                        $type = AbstractValue::getXmlRpcTypeByValue($param);
                        foreach ($signatures as $signature) {
                            if (! is_array($signature)) {
                                continue;
                            }
                            if (isset($signature['parameters'][$key])) {
                                if ($signature['parameters'][$key] == $type) {
                                    break;
                                }
                            }
                        }
                    } elseif (isset($signatures[0]['parameters'][$key])) {
                        $type = $signatures[0]['parameters'][$key];
                    } else {
                        $type = null;
                    }

                    if (empty($type) || ! in_array($type, $validTypes)) {
                        $type = AbstractValue::AUTO_DETECT_TYPE;
                    }

                    $params[$key] = AbstractValue::getXmlRpcValue($param, $type);
                }
            }
        }

        $request = $this->createRequest($method, $params);

        $this->doRequest($request);

        if ($this->lastResponse->isFault()) {
            $fault = $this->lastResponse->getFault();
            /**
             * Exception thrown when an XML-RPC fault is returned
             */
            throw new Client\Exception\FaultException(
                $fault->getMessage(),
                $fault->getCode()
            );
        }

        return $this->lastResponse->getReturnValue();
    }

    /**
     * Create request object
     *
     * @param string $method
     * @param array $params
     * @return \Laminas\XmlRpc\Request
     */
    protected function createRequest($method, $params)
    {
        return new Request($method, $params);
    }
}
