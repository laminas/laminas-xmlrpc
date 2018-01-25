<?php
/**
 * @link      http://github.com/zendframework/zend-xmlrpc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\XmlRpc\Server\TestAsset;

use Zend\XmlRpc\Server\Fault;

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
