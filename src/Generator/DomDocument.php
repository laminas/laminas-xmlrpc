<?php

namespace Laminas\XmlRpc\Generator;

use DOMNode;
use Override;

/**
 * DOMDocument based implementation of a XML/RPC generator
 */
final class DomDocument extends AbstractGenerator
{
    protected \DOMDocument $dom;

    protected DOMNode $currentElement;

    /**
     * Start XML element
     */
    #[Override]
    protected function openXmlElement(string $name): void
    {
        $newElement = $this->dom->createElement($name);

        $this->currentElement = $this->currentElement->appendChild($newElement);
    }

    /**
     * Write XML text data into the currently opened XML element
     */
    #[Override]
    protected function writeTextData(string $text): void
    {
        $this->currentElement->appendChild($this->dom->createTextNode($text));
    }

    /**
     * Close a previously opened XML element
     *
     * Resets $currentElement to the next parent node in the hierarchy
     */
    #[Override]
    protected function closeXmlElement(string $name): void
    {
        if (isset($this->currentElement->parentNode)) {
            $this->currentElement = $this->currentElement->parentNode;
        }
    }

    /**
     * Save XML as a string
     */
    #[Override]
    public function saveXml(): string
    {
        return $this->dom->saveXml();
    }

    /**
     * Initializes internal objects
     */
    #[Override]
    protected function init(): void
    {
        $this->dom            = new \DOMDocument('1.0', $this->encoding);
        $this->currentElement = $this->dom;
    }
}
