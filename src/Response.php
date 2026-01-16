<?php

namespace Laminas\XmlRpc;

use Laminas\Xml\Exception\RuntimeException;
use Laminas\Xml\Security as XmlSecurity;
use Stringable;

use function is_object;
use function is_string;

/**
 * XmlRpc Response
 *
 * Container for accessing an XMLRPC return value and creating the XML response.
 */
class Response implements Stringable
{
    /**
     * Return value
     */
    protected mixed $return;

    /**
     * Return type
     */
    protected string $type;

    /**
     * Response character encoding
     */
    protected string $encoding = 'UTF-8';

    /**
     * Fault, if response is a fault response
     */
    protected null|Fault $fault;

    /**
     * Constructor
     *
     * Can optionally pass in the return value and type hinting; otherwise, the
     * return value can be set via {@link setReturnValue()}.
     */
    public function __construct(mixed $return = null, string|null $type = null)
    {
        $this->setReturnValue($return, $type);
    }

    /**
     * Set encoding to use in response
     */
    public function setEncoding(string $encoding): Response
    {
        $this->encoding = $encoding;
        AbstractValue::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current response encoding
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Set the return value
     *
     * Sets the return value, with optional type hinting if provided.
     */
    public function setReturnValue(mixed $value, string|null $type = null): void
    {
        $this->return = $value;
        $this->type   = (string) $type;
    }

    /**
     * Retrieve the return value
     */
    public function getReturnValue(): mixed
    {
        return $this->return;
    }

    /**
     * Retrieve the XMLRPC value for the return value
     */
    protected function getXmlRpcReturn(): AbstractValue
    {
        return AbstractValue::getXmlRpcValue($this->return);
    }

    /**
     * Is the response a fault response?
     */
    public function isFault(): bool
    {
        return $this->fault instanceof Fault;
    }

    /**
     * Returns the fault, if any.
     */
    public function getFault(): null|Fault
    {
        return $this->fault;
    }

    /**
     * Load a response from an XML response
     *
     * Attempts to load a response from an XMLRPC response, autodetecting if it
     * is a fault response.
     *
     * You may optionally pass a bitmask of LIBXML options via the
     * $libXmlOptions parameter; as an example, you might use LIBXML_PARSEHUGE.
     * See https://www.php.net/manual/en/libxml.constants.php for a full list.
     *
     * @param int $libXmlOptions Bitmask of LIBXML options to use for XML * operations
     * @throws Exception\ValueException If invalid XML.
     * @return bool True if a valid XMLRPC response, false if a fault
     * response or invalid input
     */
    public function loadXml(string $response, int $libXmlOptions = 0): bool
    {
        if (! is_string($response)) {
            $this->fault = new Fault(650);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            $xml = XmlSecurity::scan($response, null, $libXmlOptions);
        } catch (RuntimeException $e) {
            $this->fault = new Fault(651);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        if (isset($xml->fault) && is_object($xml->fault)) {
            // fault response
            $this->fault = new Fault();
            $this->fault->setEncoding($this->getEncoding());
            $this->fault->loadXml($response);
            return false;
        }

        if (! isset($xml->params)) {
            // Invalid response
            $this->fault = new Fault(652);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            if (! isset($xml->params->param, $xml->params->param->value)) {
                throw new Exception\ValueException('Missing XML-RPC value in XML');
            }
            $valueXml = $xml->params->param->value->asXML();
            $value    = AbstractValue::getXmlRpcValue($valueXml, AbstractValue::XML_STRING, $libXmlOptions);
        } catch (Exception\ValueException) {
            $this->fault = new Fault(653);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->setReturnValue($value->getValue());
        return true;
    }

    /**
     * Return response as XML
     */
    public function saveXml(): string
    {
        $value     = $this->getXmlRpcReturn();
        $generator = AbstractValue::getGenerator();
        $generator->openElement('methodResponse')
                  ->openElement('params')
                  ->openElement('param');
        $value->generateXml();
        $generator->closeElement('param')
                  ->closeElement('params')
                  ->closeElement('methodResponse');

        return $generator->flush();
    }

    /**
     * Return XML response
     */
    public function __toString(): string
    {
        return $this->saveXML();
    }
}
