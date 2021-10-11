<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

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
