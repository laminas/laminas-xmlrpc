<?php

namespace Laminas\XmlRpc\Generator;

/**
 * Abstract XML generator adapter
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /**
     * XML encoding string
     *
     * @var string
     */
    protected $encoding;

    /**
     * Construct new instance of the generator
     *
     * @param string $encoding XML encoding, default UTF-8
     */
    public function __construct($encoding = 'UTF-8')
    {
        $this->setEncoding($encoding);
        $this->init();
    }

    /**
     * Initialize internal objects
     *
     * @return void
     */
    abstract protected function init();

    /**
     * Start XML element
     *
     * Method opens a new XML element with an element name and an optional value
     *
     * @param string $name XML tag name
     * @param string $value Optional value of the XML tag
     * @return AbstractGenerator Fluent interface
     */
    public function openElement($name, $value = null)
    {
        $this->openXmlElement($name);
        if ($value !== null) {
            $this->writeTextData($value);
        }

        return $this;
    }

    /**
     * End of an XML element
     *
     * Method marks the end of an XML element
     *
     * @param string $name XML tag name
     * @return AbstractGenerator Fluent interface
     */
    public function closeElement($name)
    {
        $this->closeXmlElement($name);

        return $this;
    }

    /**
     * Return encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set XML encoding
     *
     * @param  string $encoding
     * @return AbstractGenerator
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Returns the XML as a string and flushes all internal buffers
     *
     * @return string
     */
    public function flush()
    {
        $xml = $this->saveXml();
        $this->init();
        return $xml;
    }

    /**
     * Returns XML without document declaration
     *
     * @return string
     */
    public function __toString()
    {
        return $this->stripDeclaration($this->saveXml());
    }

    /**
     * Removes XML declaration from a string
     *
     * @param  string $xml
     * @return string
     */
    public function stripDeclaration($xml)
    {
        return preg_replace('/<\?xml version="1.0"( encoding="[^\"]*")?\?>\n/u', '', $xml);
    }

    /**
     * Start XML element
     *
     * @param string $name XML element name
     */
    abstract protected function openXmlElement($name);

    /**
     * Write XML text data into the currently opened XML element
     *
     * @param string $text
     */
    abstract protected function writeTextData($text);

    /**
     * End XML element
     *
     * @param string $name
     */
    abstract protected function closeXmlElement($name);
}
