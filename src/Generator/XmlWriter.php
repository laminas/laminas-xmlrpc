<?php

namespace Laminas\XmlRpc\Generator;

/**
 * XML generator adapter based on XMLWriter
 */
class XmlWriter extends AbstractGenerator
{
    /**
     * XMLWriter instance
     */
    protected \XMLWriter $xmlWriter;

    /**
     * Initialized XMLWriter instance
     *
     * @return void
     */
    protected function init(): void
    {
        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openMemory();
        $this->xmlWriter->startDocument('1.0', $this->encoding);
    }

    /**
     * Open a new XML element
     *
     * @param string $name XML element name
     */
    protected function openXmlElement(string $name): void
    {
        $this->xmlWriter->startElement($name);
    }

    /**
     * Write XML text data into the currently opened XML element
     *
     * @param string $text XML text data
     */
    protected function writeTextData(string $text): void
    {
        $this->xmlWriter->text($text);
    }

    /**
     * Close a previously opened XML element
     *
     * @param string $name
     */
    protected function closeXmlElement(string $name): void
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Emit XML document
     */
    public function saveXml(): string
    {
        return (string) $this->xmlWriter->flush(false);
    }
}
