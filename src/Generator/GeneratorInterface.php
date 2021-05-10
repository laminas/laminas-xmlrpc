<?php

namespace Laminas\XmlRpc\Generator;

/**
 * XML generator adapter interface
 */
interface GeneratorInterface
{
    public function getEncoding();
    public function setEncoding($encoding);
    public function openElement($name, $value = null);
    public function closeElement($name);

    /**
     * Return XML as a string
     *
     * @return string
     */
    public function saveXml();

    public function stripDeclaration($xml);
    public function flush();
    public function __toString();
}
