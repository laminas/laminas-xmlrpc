<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\Server\TestAsset;

use Laminas\XmlRpc\Server\Fault;

class Observer
{
    private static ?Observer $instance = null;

    public array $observed = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (! static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function observe(Fault $fault): void
    {
        self::getInstance()->observed[] = $fault;
    }

    public static function clearObserved(): void
    {
        self::getInstance()->observed = [];
    }

    public static function getObserved(): array
    {
        return self::getInstance()->observed;
    }
}
