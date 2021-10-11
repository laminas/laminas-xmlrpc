<?php

namespace Laminas\XmlRpc\Generator;

/**
 * XML generator adapter interface
 */
interface GeneratorInterface
{
    public function getEncoding();

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding);

    /**
     * @param string $name
     * @param string $value
     */
    public function openElement($name, $value = null);

    /**
     * @param string $name
     */
    public function closeElement($name);

    /**
     * Return XML as a string
     *
     * @return string
     */
    public function saveXml();

    /**
     * @param  string $xml
     * @return string
     */
    public function stripDeclaration($xml);

    public function flush();

    public function __toString();
}
