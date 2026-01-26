<?php

namespace Laminas\XmlRpc\Generator;

use function preg_replace;

/**
 * Abstract XML generator adapter
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /**
     * XML encoding string
     */
    protected string $encoding;

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
     * @param string|null $value Optional value of the XML tag
     * @return AbstractGenerator Fluent interface
     */
    #[\Override]
    public function openElement(string $name, string|null $value = null): AbstractGenerator
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
    #[\Override]
    public function closeElement(string $name): AbstractGenerator
    {
        $this->closeXmlElement($name);

        return $this;
    }

    /**
     * Return encoding
     */
    #[\Override]
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Set XML encoding
     */
    #[\Override]
    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
    }

    /**
     * Returns the XML as a string and flushes all internal buffers
     */
    #[\Override]
    public function flush(): string
    {
        $xml = $this->saveXml();
        $this->init();
        return $xml;
    }

    /**
     * Returns XML without document declaration
     */
    public function __toString(): string
    {
        return $this->stripDeclaration($this->saveXml());
    }

    /**
     * Removes XML declaration from a string
     */
    #[\Override]
    public function stripDeclaration(string $xml): string
    {
        return preg_replace('/<\?xml version="1.0"( encoding="[^\"]*")?\?>\n/u', '', $xml);
    }

    /**
     * Start XML element
     *
     * @param string $name XML element name
     */
    abstract protected function openXmlElement(string $name): void;

    /**
     * Write XML text data into the currently opened XML element
     */
    abstract protected function writeTextData(string $text): void;

    /**
     * End XML element
     */
    abstract protected function closeXmlElement(string $name): void;
}
