<?php

namespace Laminas\XmlRpc\Generator;

/**
 * XML generator adapter based on XMLWriter
 */
final class XmlWriter extends AbstractGenerator
{
    /**
     * XMLWriter instance
     */
    protected \XMLWriter $xmlWriter;

    /**
     * Initialized XMLWriter instance
     */
    #[\Override]
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
    #[\Override]
    protected function openXmlElement(string $name): void
    {
        $this->xmlWriter->startElement($name);
    }

    /**
     * Write XML text data into the currently opened XML element
     *
     * @param string $text XML text data
     */
    #[\Override]
    protected function writeTextData(string $text): void
    {
        $this->xmlWriter->text($text);
    }

    /**
     * Close a previously opened XML element
     */
    #[\Override]
    protected function closeXmlElement(string $name): void
    {
        $this->xmlWriter->endElement();
    }

    /**
     * Emit XML document
     */
    #[\Override]
    public function saveXml(): string
    {
        return (string) $this->xmlWriter->flush(false);
    }
}
