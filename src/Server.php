<?php

namespace Laminas\XmlRpc;

use DateTime;
use Exception;
use Laminas\Server\AbstractServer;
use Laminas\Server\Definition;
use Laminas\Server\Reflection;
use Laminas\XmlRpc\Fault as XmlRpcFault;
use Laminas\XmlRpc\Response;
use Laminas\XmlRpc\Response\Http;
use Laminas\XmlRpc\Server\Exception\InvalidArgumentException;
use Laminas\XmlRpc\Server\Fault;

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
use function str_starts_with;

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
     */
    protected string $encoding = 'UTF-8';

    /**
     * Request processed
     */
    protected null|Request $request = null;

    /**
     * Class to use for responses; defaults to {@link Response\Http}
     */
    protected string $responseClass = Http::class;

    /**
     * Dispatch table of name => method pairs
     *
     * @var Definition
     */
    protected $table;

    /**
     * PHP types => XML-RPC types
     */
    protected array $typeMap = [
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
     */
    protected bool $sendArgumentsToAllMethods = true;

    /**
     * Flag: whether or not {@link handle()} should return a response instead
     * of automatically emitting it.
     */
    protected bool $returnResponse = false;

    /**
     * Last response results.
     */
    protected Response|Fault|XmlRpcFault $response;

    /** @internal */
    public Server\System $system;

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
     * @throws Server\Exception\BadMethodCallException
     */
    public function __call(string $method, array $params): mixed
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
     */
    public function addFunction($function, $namespace = ''): void
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
     * @throws InvalidArgumentException On invalid input.
     */
    public function setClass($class, $namespace = '', mixed $argv = null): void
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
     * @param string|Exception|null $fault
     * @param int $code
     */
    public function fault($fault = null, $code = 404): Fault
    {
        if (! $fault instanceof Exception) {
            $fault = (string) $fault;
            if (empty($fault)) {
                $fault = 'Unknown Error';
            }
            $fault = new Server\Exception\RuntimeException($fault, $code);
        }

        return Fault::getInstance($fault);
    }

    /**
     * Set return response flag
     *
     * If true, {@link handle()} will return the response instead of
     * automatically sending it back to the requesting client.
     *
     * The response is always available via {@link getResponse()}.
     *
     * @param bool $flag
     */
    public function setReturnResponse($flag = true): Server
    {
        $this->returnResponse = (bool) $flag;
        return $this;
    }

    /**
     * Retrieve return response flag
     */
    public function getReturnResponse(): bool
    {
        return $this->returnResponse;
    }

    /**
     * Handle an xmlrpc call
     *
     * @param Request|bool $request
     */
    public function handle($request = false): Response|Fault|XmlRpcFault|null
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
            return null;
        }

        return $response;
    }

    /**
     * Load methods as returned from {@link getFunctions}
     *
     * Typically, you will not use this method; it will be called using the
     * results pulled from {@link Laminas\XmlRpc\Server\Cache::get()}.
     *
     * @param array<int, Definition>|Definition $definition
     * @throws InvalidArgumentException On invalid input.
     */
    public function loadFunctions($definition): void
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
            if (str_starts_with($key, 'system.')) {
                continue;
            }
            $this->table->addMethod($method, $key);
        }
    }

    /**
     * Set encoding
     */
    public function setEncoding(string $encoding): Server
    {
        $this->encoding = $encoding;
        AbstractValue::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current encoding
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Do nothing; persistence is handled via {@link Laminas\XmlRpc\Server\Cache}
     */
    public function setPersistence(mixed $mode): void
    {
    }

    /**
     * Set the request object
     *
     * @throws InvalidArgumentException On invalid request class or object.
     */
    public function setRequest(string|Request $request): Server
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
     */
    public function getRequest(): null|Request
    {
        return $this->request;
    }

    /**
     * Last response.
     */
    public function getResponse(): Response|Fault|XmlRpcFault
    {
        return $this->response;
    }

    /**
     * Set the class to use for the response
     *
     * @throws InvalidArgumentException If invalid response class.
     * @return bool True if class was set, false if not
     */
    public function setResponseClass(string $class): bool
    {
        if (! class_exists($class) || ! is_subclass_of($class, Response::class)) {
            throw new InvalidArgumentException('Invalid response class');
        }
        $this->responseClass = $class;
        return true;
    }

    /**
     * Retrieve current response class
     */
    public function getResponseClass(): string
    {
        return $this->responseClass;
    }

    /**
     * Retrieve dispatch table
     */
    public function getDispatchTable(): Definition
    {
        return $this->table;
    }

    /**
     * Returns a list of registered methods
     *
     * Returns an array of dispatchables (Laminas\Server\Reflection\ReflectionFunction,
     * ReflectionMethod, and ReflectionClass items).
     */
    public function getFunctions(): array
    {
        return $this->table->toArray();
    }

    /**
     * Retrieve system object
     */
    public function getSystem(): Server\System
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
     */
    public function sendArgumentsToAllMethods(bool|null $flag = null): self|bool
    {
        if ($flag === null) {
            return $this->sendArgumentsToAllMethods;
        }

        $this->sendArgumentsToAllMethods = $flag;
        return $this;
    }

    // @codingStandardsIgnoreStart
    /**
     * Map PHP type to XML-RPC type
     * 
     * @param string $type
     */
    protected function _fixType($type): string
    {
        return $this->typeMap[$type] ?? 'void';
    }
    // @codingStandardsIgnoreEnd

    /**
     * Handle an xmlrpc call (actual work)
     *
     * @throws Server\Exception\RuntimeException
     * Laminas\XmlRpc\Server\Exceptions are thrown for internal errors; otherwise,
     * any other exception may be thrown by the callback
     */
    protected function handleRequest(Request $request): Response
    {
        $method = $request->getMethod();

        // Check for valid method
        if (! $this->table->hasMethod($method)) {
            throw new Server\Exception\RuntimeException('Method "' . $method . '" does not exist', 620);
        }

        $info = $this->table->getMethod($method);
        if ($info === null || $info === false) {
            throw new Server\Exception\RuntimeException('Method "' . $method . '" does not exist', 620);
        }

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
     */
    protected function registerSystemMethods(): void
    {
        $system       = new Server\System($this);
        $this->system = $system;
        $this->setClass($system, 'system');
    }
}
