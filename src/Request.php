<?php

namespace Laminas\XmlRpc;

use DOMDocument;
use Exception;
use Laminas\Stdlib\ErrorHandler;
use Laminas\XmlRpc\Exception\ValueException;
use SimpleXMLElement;
use Stringable;

use function assert;
use function count;
use function func_get_args;
use function func_num_args;
use function is_array;
use function is_string;
use function libxml_use_internal_errors;
use function preg_match;
use function simplexml_import_dom;

use const XML_DOCUMENT_TYPE_NODE;

/**
 * XmlRpc Request object
 *
 * Encapsulates an XmlRpc request, holding the method call and all parameters.
 * Provides accessors for these, as well as the ability to load from XML and to
 * create the XML request string.
 *
 * Additionally, if errors occur setting the method or parsing XML, a fault is
 * generated and stored in {@link $fault}; developers may check for it using
 * {@link isFault()} and {@link getFault()}.
 */
class Request implements Stringable
{
    /**
     * Request character encoding
     */
    protected string $encoding = 'UTF-8';

    /**
     * Method to call
     */
    protected string|null $method = null;

    /**
     * XML request
     */
    protected string $xml;

    /**
     * Method parameters
     */
    protected array $params = [];

    /**
     * Fault object, if any
     */
    protected Fault|null $fault = null;

    /**
     * XML-RPC type for each param
     */
    protected array $types = [];

    /**
     * XML-RPC request params
     */
    protected array $xmlRpcParams = [];

    /**
     * Create a new XML-RPC request
     */
    public function __construct(string|null $method = null, array|null $params = null)
    {
        if ($method !== null) {
            $this->setMethod($method);
        }

        if ($params !== null) {
            $this->setParams($params);
        }
    }

    /**
     * Set encoding to use in request
     */
    public function setEncoding(string $encoding): Request
    {
        $this->encoding = $encoding;
        AbstractValue::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current request encoding
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Set method to call
     *
     * @return bool Returns true on success, false if method name is invalid
     */
    public function setMethod(string $method): bool
    {
        if (! is_string($method) || ! preg_match('/^[a-z0-9_.:\\\\\/]+$/i', $method)) {
            $this->fault = new Fault(634, 'Invalid method name ("' . $method . '")');
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->method = $method;
        return true;
    }

    /**
     * Retrieve call method
     */
    public function getMethod(): string|null
    {
        return $this->method;
    }

    /**
     * Add a parameter to the parameter stack
     *
     * Adds a parameter to the parameter stack, associating it with the type
     * $type if provided
     *
     * @param ((string|string[])[]|string)[]|Value\Text|int|string $value
     * @param string $type Optional; type hinting
     */
    public function addParam(array|string|int|Value\Text $value, string|null $type = null): void
    {
        $this->params[] = $value;
        if (null === $type) {
            // Detect type if not provided explicitly
            if ($value instanceof AbstractValue) {
                $type = $value->getType();
            } else {
                $xmlRpcValue = AbstractValue::getXmlRpcValue($value);
                $type        = $xmlRpcValue->getType();
            }
        }
        $this->types[]        = $type;
        $this->xmlRpcParams[] = ['value' => $value, 'type' => $type];
    }

    /**
     * Set the parameters array
     *
     * If called with a single, array value, that array is used to set the
     * parameters stack. If called with multiple values or a single non-array
     * value, the arguments are used to set the parameters stack.
     *
     * Best is to call with array of the format, in order to allow type hinting
     * when creating the XMLRPC values for each parameter:
     * <code>
     * $array = array(
     *     array(
     *         'value' => $value,
     *         'type'  => $type
     *     )[, ... ]
     * );
     * </code>
     *
     * @access public
     */
    public function setParams(): void
    {
        $argc = func_num_args();
        $argv = func_get_args();
        if (0 === $argc) {
            return;
        }

        if ((1 === $argc) && is_array($argv[0])) {
            $params     = [];
            $types      = [];
            $wellFormed = true;
            foreach ($argv[0] as $arg) {
                if (! is_array($arg) || ! isset($arg['value'])) {
                    $wellFormed = false;
                    break;
                }
                $params[] = $arg['value'];

                if (! isset($arg['type'])) {
                    $xmlRpcValue = AbstractValue::getXmlRpcValue($arg['value']);
                    $arg['type'] = $xmlRpcValue->getType();
                }
                $types[] = $arg['type'];
            }
            if ($wellFormed) {
                $this->xmlRpcParams = $argv[0];
                $this->params       = $params;
                $this->types        = $types;
            } else {
                $this->params = $argv[0];
                $this->types  = [];
                $xmlRpcParams = [];
                foreach ($argv[0] as $arg) {
                    if ($arg instanceof AbstractValue) {
                        $type = $arg->getType();
                    } else {
                        $xmlRpcValue = AbstractValue::getXmlRpcValue($arg);
                        $type        = $xmlRpcValue->getType();
                    }
                    $xmlRpcParams[] = ['value' => $arg, 'type' => $type];
                    $this->types[]  = $type;
                }
                $this->xmlRpcParams = $xmlRpcParams;
            }
            return;
        }

        $this->params = $argv;
        $this->types  = [];
        $xmlRpcParams = [];
        foreach ($argv as $arg) {
            if ($arg instanceof AbstractValue) {
                $type = $arg->getType();
            } else {
                $xmlRpcValue = AbstractValue::getXmlRpcValue($arg);
                $type        = $xmlRpcValue->getType();
            }
            $xmlRpcParams[] = ['value' => $arg, 'type' => $type];
            $this->types[]  = $type;
        }
        $this->xmlRpcParams = $xmlRpcParams;
    }

    /**
     * Retrieve the array of parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Return parameter types
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Load XML and parse into request components
     *
     * You may optionally pass a bitmask of LIBXML options via the
     * $libXmlOptions parameter; as an example, you might use LIBXML_PARSEHUGE.
     * See https://www.php.net/manual/en/libxml.constants.php for a full list.
     *
     * @param int $libXmlOptions Bitmask of LIBXML options to use for XML * operations
     * @throws ValueException If invalid XML.
     * @return bool True on success, false if an error occurred.
     */
    public function loadXml(string|object $request, int $libXmlOptions = 0): bool
    {
        if (! is_string($request)) {
            $this->fault = new Fault(635);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        $xmlErrorsFlag = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument();
            $dom->loadXML($request, $libXmlOptions);
            foreach ($dom->childNodes as $child) {
                if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    throw new ValueException(
                        'Invalid XML: Detected use of illegal DOCTYPE'
                    );
                }
            }
            ErrorHandler::start();
            $xml   = simplexml_import_dom($dom);
            $error = ErrorHandler::stop();
            libxml_use_internal_errors($xmlErrorsFlag);
        } catch (Exception) {
            // Not valid XML
            $this->fault = new Fault(631);
            $this->fault->setEncoding($this->getEncoding());
            libxml_use_internal_errors($xmlErrorsFlag);
            return false;
        }
        if (! $xml instanceof SimpleXMLElement || $error) {
            // Not valid XML
            $this->fault = new Fault(631);
            $this->fault->setEncoding($this->getEncoding());
            libxml_use_internal_errors($xmlErrorsFlag);
            return false;
        }

        // Check for method name
        if (! isset($xml->methodName) || (string) $xml->methodName === '') {
            // Missing method name
            $this->fault = new Fault(632);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->method = (string) $xml->methodName;

        // Check for parameters
        if ($xml->params instanceof SimpleXMLElement && $xml->params->count() > 0) {
            $types    = [];
            $argv     = [];
            $children = $xml->params->children();
            assert($children !== null);
            foreach ($children as $param) {
                if (! isset($param->value)) {
                    $this->fault = new Fault(633);
                    $this->fault->setEncoding($this->getEncoding());
                    return false;
                }

                try {
                    $param   = AbstractValue::getXmlRpcValue($param->value, AbstractValue::XML_STRING);
                    $types[] = $param->getType();
                    $argv[]  = $param->getValue();
                } catch (Exception) {
                    $this->fault = new Fault(636);
                    $this->fault->setEncoding($this->getEncoding());
                    return false;
                }
            }

            $this->types  = $types;
            $this->params = $argv;
        }

        $this->xml = $request;

        return true;
    }

    /**
     * Does the current request contain errors and should it return a fault
     * response?
     */
    public function isFault(): bool
    {
        return $this->fault instanceof Fault;
    }

    /**
     * Retrieve the fault response, if any
     */
    public function getFault(): null|Fault
    {
        return $this->fault;
    }

    /**
     * Retrieve method parameters as XMLRPC values
     */
    protected function getXmlRpcParams(): array
    {
        $params = [];
        if (is_array($this->xmlRpcParams)) {
            foreach ($this->xmlRpcParams as $param) {
                $value = $param['value'];
                $type  = $param['type'] ?: AbstractValue::AUTO_DETECT_TYPE;

                if (! $value instanceof AbstractValue) {
                    $value = AbstractValue::getXmlRpcValue($value, $type);
                }
                $params[] = $value;
            }
        }

        return $params;
    }

    /**
     * Create XML request
     */
    public function saveXml(): string
    {
        $args   = $this->getXmlRpcParams();
        $method = $this->getMethod();

        $generator = AbstractValue::getGenerator();
        $generator->openElement('methodCall')
                  ->openElement('methodName', $method)
                  ->closeElement('methodName');

        if (is_array($args) && count($args)) {
            $generator->openElement('params');

            foreach ($args as $arg) {
                $generator->openElement('param');
                $arg->generateXml();
                $generator->closeElement('param');
            }
            $generator->closeElement('params');
        }
        $generator->closeElement('methodCall');

        return $generator->flush();
    }

    /**
     * Return XML request
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->saveXML();
    }
}
