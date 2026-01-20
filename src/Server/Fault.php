<?php

namespace Laminas\XmlRpc\Server;

use Exception;
use Laminas\XmlRpc\Server\Exception\ExceptionInterface;

use function array_keys;
use function class_exists;
use function is_array;
use function is_callable;
use function is_string;

/**
 * XMLRPC Server Faults
 *
 * Encapsulates an exception for use as an XMLRPC fault response. Valid
 * exception classes that may be used for generating the fault code and fault
 * string can be attached using {@link attachFaultException()}; all others use a
 * generic '404 Unknown error' response.
 *
 * You may also attach fault observers, which would allow you to monitor
 * particular fault cases; this is done via {@link attachObserver()}. Observers
 * need only implement a static 'observe' method.
 *
 * To allow method chaining, you may use the {@link getInstance()} factory
 * to instantiate a Laminas\XmlRpc\Server\Fault.
 */
class Fault extends \Laminas\XmlRpc\Fault
{
    protected Exception $exception;

    /**
     * Array of exception classes that may define xmlrpc faults
     *
     * @var array<class-string, bool>
     */
    protected static array $faultExceptionClasses = [ExceptionInterface::class => true];

    /**
     * Array of fault observers
     *
     * @var array<int, Exception>
     */
    protected static array $observers = [];

    public function __construct(Exception $e)
    {
        $this->exception = $e;
        $code            = 404;
        $message         = 'Unknown error';

        foreach (array_keys(static::$faultExceptionClasses) as $class) {
            if ($e instanceof $class) {
                $code    = $e->getCode();
                $message = $e->getMessage();
                break;
            }
        }

        parent::__construct($code, $message);

        // Notify exception observers, if present
        if (! empty(static::$observers)) {
            foreach (array_keys(static::$observers) as $observer) {
                $observer::observe($this);
            }
        }
    }

    /**
     * Return Laminas\XmlRpc\Server\Fault instance
     */
    public static function getInstance(Exception $e): Fault
    {
        return new static($e);
    }

    /**
     * Attach valid exceptions that can be used to define xmlrpc faults
     *
     * @param string|array $classes Class name or array of class names
     */
    public static function attachFaultException(string|array $classes): void
    {
        if (! is_array($classes)) {
            $classes = (array) $classes;
        }

        foreach ($classes as $class) {
            if (is_string($class) && class_exists($class)) {
                static::$faultExceptionClasses[$class] = true;
            }
        }
    }

    /**
     * Detach fault exception classes
     *
     * @param class-string|array<int, class-string> $classes Class name or array of class names
     */
    public static function detachFaultException(string|array $classes): void
    {
        if (! is_array($classes)) {
            $classes = (array) $classes;
        }

        foreach ($classes as $class) {
            if (is_string($class) && isset(static::$faultExceptionClasses[$class])) {
                unset(static::$faultExceptionClasses[$class]);
            }
        }
    }

    /**
     * Attach an observer class
     *
     * Allows observation of xmlrpc server faults, thus allowing logging or mail
     * notification of fault responses on the xmlrpc server.
     *
     * Expects a valid class name; that class must have a public static method
     * 'observe' that accepts an exception as its sole argument.
     *
     * @param class-string $class
     */
    public static function attachObserver(string $class): bool
    {
        if (! is_string($class) || ! class_exists($class) || ! is_callable([$class, 'observe'])) {
            return false;
        }

        if (! isset(static::$observers[$class])) {
            static::$observers[$class] = true;
        }

        return true;
    }

    /**
     * Detach an observer
     */
    public static function detachObserver(string $class): bool
    {
        if (! isset(static::$observers[$class])) {
            return false;
        }

        unset(static::$observers[$class]);
        return true;
    }

    /**
     * Retrieve the exception
     *
     * @access public
     */
    public function getException(): Exception
    {
        return $this->exception;
    }
}
