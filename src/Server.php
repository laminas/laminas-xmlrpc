<?php

namespace Laminas\XmlRpc;

use DateTime;
use Exception;
use Laminas\Server\AbstractServer;
use Laminas\Server\Definition;
use Laminas\Server\Reflection;
use Laminas\XmlRpc\Response;
use Laminas\XmlRpc\Response\Http;
use Laminas\XmlRpc\Server\Exception\InvalidArgumentException;

use function array_merge;
use function array_slice;
use function call_user_func_array;
use function class_exists;
use function count;
use function func_get_args;
use function func_num_args;
use function function_exists;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function method_exists;
use function substr;

/**
 * An XML-RPC server implementation
 *
 * Example:
 * <code>
 * use Laminas\XmlRpc;
 *
 * // Instantiate server
 * $server = new XmlRpc\Server();
 *
 * // Allow some exceptions to report as fault responses:
 * XmlRpc\Server\Fault::attachFaultException('My\\Exception');
 * XmlRpc\Server\Fault::attachObserver('My\\Fault\\Observer');
 *
 * // Get or build dispatch table:
 * if (!XmlRpc\Server\Cache::get($filename, $server)) {
 *
 *     // Attach Some_Service_Class in 'some' namespace
 *     $server->setClass('Some\\Service\\Class', 'some');
 *
 *     // Attach Another_Service_Class in 'another' namespace
 *     $server->setClass('Another\\Service\\Class', 'another');
 *
 *     // Create dispatch table cache file
 *     XmlRpc\Server\Cache::save($filename, $server);
 * }
 *
 * $response = $server->handle();
 * echo $response;
 * </code>
 */
class Server extends AbstractServer
{
    /**
     * Character encoding
     *
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Request processed
     *
     * @var null|Request
     */
    protected $request;

    /**
     * Class to use for responses; defaults to {@link Response\Http}
     *
     * @var string
     */
    protected $responseClass = Http::class;

    /**
     * Dispatch table of name => method pairs
     *
     * @var Definition
     */
    protected $table;

    /**
     * PHP types => XML-RPC types
     *
     * @var array
     */
    protected $typeMap = [
        'i4'               => 'i4',
        'int'              => 'int',
        'integer'          => 'int',
        'i8'               => 'i8',
        'ex:i8'            => 'i8',
        'double'           => 'double',
        'float'            => 'double',
        'real'             => 'double',
        'boolean'          => 'boolean',
        'bool'             => 'boolean',
        'true'             => 'boolean',
        'false'            => 'boolean',
        'string'           => 'string',
        'str'              => 'string',
        'base64'           => 'base64',
        'dateTime.iso8601' => 'dateTime.iso8601',
        'date'             => 'dateTime.iso8601',
        'time'             => 'dateTime.iso8601',
        DateTime::class    => 'dateTime.iso8601',
        'array'            => 'array',
        'struct'           => 'struct',
        'null'             => 'nil',
        'nil'              => 'nil',
        'ex:nil'           => 'nil',
        'void'             => 'void',
        'mixed'            => 'struct',
    ];

    /**
     * Send arguments to all methods or just constructor?
     *
     * @var bool
     */
    protected $sendArgumentsToAllMethods = true;

    /**
     * Flag: whether or not {@link handle()} should return a response instead
     * of automatically emitting it.
     *
     * @var bool
     */
    protected $returnResponse = false;

    /**
     * Last response results.
     *
     * @var Response
     */
    protected $response;

    /**
     * @internal
     *
     * @var Server\System
     */
    public $system;

    /**
     * Constructor
     *
     * Creates system.* methods.
     */
    public function __construct()
    {
        $this->table = new Definition();
        $this->registerSystemMethods();
    }

    /**
     * Proxy calls to system object
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     * @throws Server\Exception\BadMethodCallException
     */
    public function __call($method, $params)
    {
        $system = $this->getSystem();
        if (! method_exists($system, $method)) {
            throw new Server\Exception\BadMethodCallException('Unknown instance method called on server: ' . $method);
        }
        return call_user_func_array([$system, $method], $params);
    }

    /**
     * Attach a callback as an XMLRPC method
     *
     * Attaches a callback as an XMLRPC method, prefixing the XMLRPC method name
     * with $namespace, if provided. Reflection is done on the callback's
     * docblock to create the methodHelp for the XMLRPC method.
     *
     * Additional arguments to pass to the function at dispatch may be passed;
     * any arguments following the namespace will be aggregated and passed at
     * dispatch time.
     *
     * @param string|array|callable $function  Valid callback
     * @param string                $namespace Optional namespace prefix
     * @throws InvalidArgumentException
     * @return void
     */
    public function addFunction($function, $namespace = '')
    {
        if (! is_string($function) && ! is_array($function)) {
            throw new InvalidArgumentException('Unable to attach function; invalid', 611);
        }

        $argv = null;
        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $function = (array) $function;
        foreach ($function as $func) {
            if (! is_string($func) || ! function_exists($func)) {
                throw new InvalidArgumentException('Unable to attach function; invalid', 611);
            }
            $reflection = Reflection::reflectFunction($func, $argv, $namespace);
            $this->_buildSignature($reflection);
        }
    }

    /**
     * Attach class methods as XMLRPC method handlers
     *
     * $class may be either a class name or an object. Reflection is done on the
     * class or object to determine the available public methods, and each is
     * attached to the server as an available method; if a $namespace has been
     * provided, that namespace is used to prefix the XMLRPC method names.
     *
     * Any additional arguments beyond $namespace will be passed to a method at
     * invocation.
     *
     * @param string|object $class
     * @param string $namespace Optional
     * @param mixed $argv Optional arguments to pass to methods
     * @return void
     * @throws InvalidArgumentException On invalid input.
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        if (is_string($class) && ! class_exists($class)) {
            throw new InvalidArgumentException('Invalid method class', 610);
        }

        if (2 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 2);
        }

        $dispatchable = Reflection::reflectClass($class, $argv, $namespace);
        foreach ($dispatchable->getMethods() as $reflection) {
            $this->_buildSignature($reflection, $class);
        }
    }

    /**
     * Raise an xmlrpc server fault
     *
     * @param string|Exception $fault
     * @param int $code
     * @return Server\Fault
     */
    public function fault($fault = null, $code = 404)
    {
        if (! $fault instanceof Exception) {
            $fault = (string) $fault;
            if (empty($fault)) {
                $fault = 'Unknown Error';
            }
            $fault = new Server\Exception\RuntimeException($fault, $code);
        }

        return Server\Fault::getInstance($fault);
    }

    /**
     * Set return response flag
     *
     * If true, {@link handle()} will return the response instead of
     * automatically sending it back to the requesting client.
     *
     * The response is always available via {@link getResponse()}.
     *
     * @param  bool $flag
     * @return Server
     */
    public function setReturnResponse($flag = true)
    {
        $this->returnResponse = (bool) $flag;
        return $this;
    }

    /**
     * Retrieve return response flag
     *
     * @return bool
     */
    public function getReturnResponse()
    {
        return $this->returnResponse;
    }

    /**
     * Handle an xmlrpc call
     *
     * @param  Request $request Optional
     * @return Response|Fault
     */
    public function handle($request = false)
    {
        // Get request
        if (
            (! $request || ! $request instanceof Request)
            && (null === ($request = $this->getRequest()))
        ) {
            $request = new Request\Http();
            $request->setEncoding($this->getEncoding());
        }

        $this->setRequest($request);

        if ($request->isFault()) {
            $response = $request->getFault();
        } else {
            try {
                $response = $this->handleRequest($request);
            } catch (Exception $e) {
                $response = $this->fault($e);
            }
        }

        // Set output encoding
        $response->setEncoding($this->getEncoding());
        $this->response = $response;

        if (! $this->returnResponse) {
            echo $response;
            return;
        }

        return $response;
    }

    /**
     * Load methods as returned from {@link getFunctions}
     *
     * Typically, you will not use this method; it will be called using the
     * results pulled from {@link Laminas\XmlRpc\Server\Cache::get()}.
     *
     * @param  array|Definition $definition
     * @return void
     * @throws InvalidArgumentException On invalid input.
     */
    public function loadFunctions($definition)
    {
        if (! is_array($definition) && ! $definition instanceof Definition) {
            if (is_object($definition)) {
                $type = $definition::class;
            } else {
                $type = gettype($definition);
            }
            throw new InvalidArgumentException(
                'Unable to load server definition; must be an array or Laminas\Server\Definition, received ' . $type,
                612
            );
        }

        $this->table->clearMethods();
        $this->registerSystemMethods();

        if ($definition instanceof Definition) {
            $definition = $definition->getMethods();
        }

        foreach ($definition as $key => $method) {
            if ('system.' === substr($key, 0, 7)) {
                continue;
            }
            $this->table->addMethod($method, $key);
        }
    }

    /**
     * Set encoding
     *
     * @param  string $encoding
     * @return Server
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        AbstractValue::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Do nothing; persistence is handled via {@link Laminas\XmlRpc\Server\Cache}
     *
     * @param  mixed $mode
     * @return void
     */
    public function setPersistence($mode)
    {
    }

    /**
     * Set the request object
     *
     * @param  string|Request $request
     * @return Server
     * @throws InvalidArgumentException On invalid request class or object.
     */
    public function setRequest($request)
    {
        if (is_string($request) && class_exists($request)) {
            $request = new $request();
            if (! $request instanceof Request) {
                throw new InvalidArgumentException('Invalid request class');
            }
            $request->setEncoding($this->getEncoding());
        } elseif (! $request instanceof Request) {
            throw new InvalidArgumentException('Invalid request object');
        }

        $this->request = $request;
        return $this;
    }

    /**
     * Return currently registered request object
     *
     * @return null|Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Last response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set the class to use for the response
     *
     * @param  string $class
     * @throws InvalidArgumentException If invalid response class.
     * @return bool True if class was set, false if not
     */
    public function setResponseClass($class)
    {
        if (! class_exists($class) || ! is_subclass_of($class, Response::class)) {
            throw new InvalidArgumentException('Invalid response class');
        }
        $this->responseClass = $class;
        return true;
    }

    /**
     * Retrieve current response class
     *
     * @return string
     */
    public function getResponseClass()
    {
        return $this->responseClass;
    }

    /**
     * Retrieve dispatch table
     *
     * @return array
     */
    public function getDispatchTable()
    {
        return $this->table;
    }

    /**
     * Returns a list of registered methods
     *
     * Returns an array of dispatchables (Laminas\Server\Reflection\ReflectionFunction,
     * ReflectionMethod, and ReflectionClass items).
     *
     * @return array
     */
    public function getFunctions()
    {
        return $this->table->toArray();
    }

    /**
     * Retrieve system object
     *
     * @return Server\System
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Send arguments to all methods?
     *
     * If setClass() is used to add classes to the server, this flag defined
     * how to handle arguments. If set to true, all methods including constructor
     * will receive the arguments. If set to false, only constructor will receive the
     * arguments
     *
     * @param  bool|null $flag
     * @return self
     */
    public function sendArgumentsToAllMethods($flag = null)
    {
        if ($flag === null) {
            return $this->sendArgumentsToAllMethods;
        }

        $this->sendArgumentsToAllMethods = (bool) $flag;
        return $this;
    }

    // @codingStandardsIgnoreStart
    /**
     * Map PHP type to XML-RPC type
     *
     * @param  string $type
     * @return string
     */
    protected function _fixType($type)
    {
        if (isset($this->typeMap[$type])) {
            return $this->typeMap[$type];
        }
        return 'void';
    }
    // @codingStandardsIgnoreEnd

    /**
     * Handle an xmlrpc call (actual work)
     *
     * @return Response
     * @throws Server\Exception\RuntimeException
     * Laminas\XmlRpc\Server\Exceptions are thrown for internal errors; otherwise,
     * any other exception may be thrown by the callback
     */
    protected function handleRequest(Request $request)
    {
        $method = $request->getMethod();

        // Check for valid method
        if (! $this->table->hasMethod($method)) {
            throw new Server\Exception\RuntimeException('Method "' . $method . '" does not exist', 620);
        }

        $info   = $this->table->getMethod($method);
        $params = $request->getParams();
        $argv   = $info->getInvokeArguments();
        if (0 < count($argv) && $this->sendArgumentsToAllMethods()) {
            $params = array_merge($params, $argv);
        }

        // Check calling parameters against signatures
        $matched   = false;
        $sigCalled = $request->getTypes();

        $sigLength = count($sigCalled);
        $paramsLen = count($params);
        if ($sigLength < $paramsLen) {
            for ($i = $sigLength; $i < $paramsLen; ++$i) {
                $xmlRpcValue = AbstractValue::getXmlRpcValue($params[$i]);
                $sigCalled[] = $xmlRpcValue->getType();
            }
        }

        $signatures = $info->getPrototypes();
        foreach ($signatures as $signature) {
            $sigParams = $signature->getParameters();
            if ($sigCalled === $sigParams) {
                $matched = true;
                break;
            }
        }
        if (! $matched) {
            throw new Server\Exception\RuntimeException('Calling parameters do not match signature', 623);
        }

        $return        = $this->_dispatch($info, $params);
        $responseClass = $this->getResponseClass();
        return new $responseClass($return);
    }

    /**
     * Register system methods with the server
     *
     * @return void
     */
    protected function registerSystemMethods()
    {
        $system       = new Server\System($this);
        $this->system = $system;
        $this->setClass($system, 'system');
    }

    /**
     * Checks if the object has this class as one of its parents
     *
     * @deprecated since laminas 2.3 requires PHP >= 5.3.23
     *
     * @see https://bugs.php.net/bug.php?id=53727
     * @see https://github.com/zendframework/zf2/pull/1807
     *
     * @param string $className
     * @param string $type
     * @return bool
     */
    protected static function isSubclassOf($className, $type)
    {
        return is_subclass_of($className, $type);
    }
}
