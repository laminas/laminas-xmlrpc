<?php

namespace Laminas\XmlRpc;

use Laminas\Xml\Exception\RuntimeException;
use Laminas\Xml\Security as XmlSecurity;
use SimpleXMLElement;
use Stringable;

use function array_reduce;
use function is_string;
use function libxml_get_errors;
use function libxml_use_internal_errors;

/**
 * XMLRPC Faults
 *
 * Container for XMLRPC faults, containing both a code and a message;
 * additionally, has methods for determining if an XML response is an XMLRPC
 * fault, as well as generating the XML for an XMLRPC fault response.
 *
 * To allow method chaining, you may only use the {@link getInstance()} factory
 * to instantiate a Laminas\XmlRpc\Server\Fault.
 */
class Fault implements Stringable
{
    /**
     * Fault code
     */
    protected int $code;

    /**
     * Fault character encoding
     */
    protected string $encoding = 'UTF-8';

    /**
     * Fault message
     */
    protected string $message;

    /**
     * Internal fault codes => messages
     */
    protected array $internal = [
        404 => 'Unknown Error',

        // 610 - 619 reflection errors
        610 => 'Invalid method class',
        611 => 'Unable to attach function or callback; not callable',
        612 => 'Unable to load array; not an array',
        613 => 'One or more method records are corrupt or otherwise unusable',

        // 620 - 629 dispatch errors
        620 => 'Method does not exist',
        621 => 'Error instantiating class to invoke method',
        622 => 'Method missing implementation',
        623 => 'Calling parameters do not match signature',

        // 630 - 639 request errors
        630 => 'Unable to read request',
        631 => 'Failed to parse request',
        632 => 'Invalid request, no method passed; request must contain a \'methodName\' tag',
        633 => 'Param must contain a value',
        634 => 'Invalid method name',
        635 => 'Invalid XML provided to request',
        636 => 'Error creating xmlrpc value',

        // 640 - 649 system.* errors
        640 => 'Method does not exist',

        // 650 - 659 response errors
        650 => 'Invalid XML provided for response',
        651 => 'Failed to parse response',
        652 => 'Invalid response',
        653 => 'Invalid XMLRPC value in response',
    ];

    /**
     * Constructor
     */
    public function __construct(int|string $code = 404, string $message = '')
    {
        $this->setCode($code);
        $code = $this->getCode();

        if (empty($message) && isset($this->internal[$code])) {
            $message = $this->internal[$code];
        } elseif (empty($message)) {
            $message = $this->internal[404];
        }
        $this->setMessage($message);
    }

    /**
     * Set the fault code
     */
    public function setCode(int|string $code): Fault
    {
        $this->code = (int) $code;
        return $this;
    }

    /**
     * Return fault code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Retrieve fault message
     */
    public function setMessage(string $message): Fault
    {
        $this->message = (string) $message;
        return $this;
    }

    /**
     * Retrieve fault message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set encoding to use in fault response
     */
    public function setEncoding(string $encoding): Fault
    {
        $this->encoding = $encoding;
        AbstractValue::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current fault encoding
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Load an XMLRPC fault from XML
     *
     * @return bool Returns true if successfully loaded fault response, false
     * if response was not a fault response
     * @throws Exception\ExceptionInterface If no or faulty XML provided, or if fault
     * response does not contain either code or message.
     */
    public function loadXml(string $fault): bool
    {
        if (! is_string($fault)) {
            throw new Exception\InvalidArgumentException('Invalid XML provided to fault');
        }

        $xmlErrorsFlag = libxml_use_internal_errors(true);
        try {
            $xml = XmlSecurity::scan($fault);
        } catch (RuntimeException $e) {
            // Unsecure XML
            throw new Exception\RuntimeException('Failed to parse XML fault: ' . $e->getMessage(), 500, $e);
        }
        if (! $xml instanceof SimpleXMLElement) {
            $errors = libxml_get_errors();
            $errors = array_reduce($errors, function ($result, $item) {
                if (empty($result)) {
                    return $item->message;
                }
                return $result . '; ' . $item->message;
            }, '');
            libxml_use_internal_errors($xmlErrorsFlag);
            throw new Exception\InvalidArgumentException('Failed to parse XML fault: ' . $errors, 500);
        }
        libxml_use_internal_errors($xmlErrorsFlag);

        // Check for fault
        if (! isset($xml->fault)) {
            // Not a fault
            return false;
        }

        if (! isset($xml->fault->value->struct)) {
            // not a proper fault
            throw new Exception\InvalidArgumentException('Invalid fault structure', 500);
        }

        $structXml = $xml->fault->value->asXML();
        $struct    = AbstractValue::getXmlRpcValue($structXml, AbstractValue::XML_STRING);
        $struct    = $struct->getValue();

        if (isset($struct['faultCode'])) {
            $code = $struct['faultCode'];
        }
        if (isset($struct['faultString'])) {
            $message = $struct['faultString'];
        }

        if (empty($code) && empty($message)) {
            throw new Exception\InvalidArgumentException('Fault code and string required');
        }

        if (empty($code)) {
            $code = '404';
        }

        if (empty($message)) {
            if (isset($this->internal[$code])) {
                $message = $this->internal[$code];
            } else {
                $message = $this->internal[404];
            }
        }

        $this->setCode($code);
        $this->setMessage($message);

        return true;
    }

    /**
     * Determine if an XML response is an XMLRPC fault
     */
    public static function isFault(string $xml): bool
    {
        $fault = new static();
        try {
            $isFault = $fault->loadXml($xml);
        } catch (Exception\ExceptionInterface) {
            $isFault = false;
        }

        return $isFault;
    }

    /**
     * Serialize fault to XML
     */
    public function saveXml(): string
    {
        // Create fault value
        $faultStruct = [
            'faultCode'   => $this->getCode(),
            'faultString' => $this->getMessage(),
        ];
        $value       = AbstractValue::getXmlRpcValue($faultStruct);

        $generator = AbstractValue::getGenerator();
        $generator->openElement('methodResponse')
                  ->openElement('fault');
        $value->generateXml();
        $generator->closeElement('fault')
                  ->closeElement('methodResponse');

        return $generator->flush();
    }

    /**
     * Return XML fault response
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->saveXML();
    }
}
