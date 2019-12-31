<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

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
