<?php

namespace Laminas\XmlRpc\Generator;

/**
 * XML generator adapter interface
 */
interface GeneratorInterface
{
    public function getEncoding(): string;

    public function setEncoding(string $encoding): void;

    public function openElement(string $name, string|null $value = null): AbstractGenerator;

    public function closeElement(string $name): AbstractGenerator;

    /**
     * Return XML as a string
     */
    public function saveXml(): string;

    public function stripDeclaration(string $xml): string;

    public function flush(): string;

    public function __toString(): string;
}
