<?php

namespace Laminas\XmlRpc;

use Laminas\Xml\Exception\RuntimeException;
use Laminas\Xml\Security as XmlSecurity;
use Laminas\XmlRpc\AbstractValue;
use Laminas\XmlRpc\Fault;

use function is_string;

/**
 * XmlRpc Response
 *
 * Container for accessing an XMLRPC return value and creating the XML response.
 */
class Response
{
    /**
     * Return value
     *
     * @var mixed
     */
    protected $return;

    /**
     * Return type
     *
     * @var string
     */
    protected $type;

    /**
     * Response character encoding
     *
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Fault, if response is a fault response
     *
     * @var null|Fault
     */
    protected $fault;

    /**
     * Constructor
     *
     * Can optionally pass in the return value and type hinting; otherwise, the
     * return value can be set via {@link setReturnValue()}.
     *
     * @param mixed $return
     * @param string $type
     */
    public function __construct($return = null, $type = null)
    {
        $this->setReturnValue($return, $type);
    }

    /**
     * Set encoding to use in response
     *
     * @param string $encoding
     * @return Response
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        AbstractValue::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current response encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set the return value
     *
     * Sets the return value, with optional type hinting if provided.
     *
     * @param mixed $value
     * @param string $type
     * @return void
     */
    public function setReturnValue($value, $type = null)
    {
        $this->return = $value;
        $this->type   = (string) $type;
    }

    /**
     * Retrieve the return value
     *
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->return;
    }

    /**
     * Retrieve the XMLRPC value for the return value
     *
     * @return AbstractValue
     */
    protected function getXmlRpcReturn()
    {
        return AbstractValue::getXmlRpcValue($this->return);
    }

    /**
     * Is the response a fault response?
     *
     * @return bool
     */
    public function isFault()
    {
        return $this->fault instanceof Fault;
    }

    /**
     * Returns the fault, if any.
     *
     * @return null|Fault
     */
    public function getFault()
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
     * @param string $response
     * @param int $libXmlOptions Bitmask of LIBXML options to use for XML * operations
     * @throws Exception\ValueException If invalid XML.
     * @return bool True if a valid XMLRPC response, false if a fault
     * response or invalid input
     */
    public function loadXml($response, int $libXmlOptions = 0)
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

        if (! empty($xml->fault)) {
            // fault response
            $this->fault = new Fault();
            $this->fault->setEncoding($this->getEncoding());
            $this->fault->loadXml($response);
            return false;
        }

        if (empty($xml->params)) {
            // Invalid response
            $this->fault = new Fault(652);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            if (! isset($xml->params) || ! isset($xml->params->param) || ! isset($xml->params->param->value)) {
                throw new Exception\ValueException('Missing XML-RPC value in XML');
            }
            $valueXml = $xml->params->param->value->asXML();
            $value    = AbstractValue::getXmlRpcValue($valueXml, AbstractValue::XML_STRING, $libXmlOptions);
        } catch (Exception\ValueException $e) {
            $this->fault = new Fault(653);
            $this->fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->setReturnValue($value->getValue());
        return true;
    }

    /**
     * Return response as XML
     *
     * @return string
     */
    public function saveXml()
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
     *
     * @return string
     */
    public function __toString()
    {
        return $this->saveXML();
    }
}
