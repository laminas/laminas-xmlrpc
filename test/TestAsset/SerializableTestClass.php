<?php

namespace LaminasTest\XmlRpc\TestAsset;

class SerializableTestClass
{
    /** @var string */
    protected $property;

    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
