<?php

namespace LaminasTest\XmlRpc\TestAsset;

class SerializableTestClass
{
    protected $property;

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }
}
