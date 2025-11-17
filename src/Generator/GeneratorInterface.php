<?php

namespace Laminas\XmlRpc\Generator;

/**
 * XML generator adapter interface
 */
interface GeneratorInterface
{
    /**
     * @return string
     */
    public function getEncoding();

    /**
     * @param string $encoding
     * @return static
     */
    public function setEncoding($encoding);

    /**
     * @param string $name
     * @param string $value
     * @return AbstractGenerator
     */
    public function openElement($name, $value = null);

    /**
     * @param string $name
     * @return AbstractGenerator
     */
    public function closeElement($name);

    /**
     * Return XML as a string
     *
     * @return string
     */
    public function saveXml();

    /**
     * @param string $xml
     * @return string
     */
    public function stripDeclaration($xml);

    /**
     * @return string
     */
    public function flush();

    public function __toString(): string;
}
