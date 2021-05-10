<?php

namespace LaminasTest\XmlRpc\Server\TestAsset;

use Laminas\XmlRpc\Server\Fault;

class Observer
{
    private static $instance = false;

    public $observed = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (! static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public static function observe(Fault $fault)
    {
        self::getInstance()->observed[] = $fault;
    }

    public static function clearObserved()
    {
        self::getInstance()->observed = [];
    }

    public static function getObserved()
    {
        return self::getInstance()->observed;
    }
}
