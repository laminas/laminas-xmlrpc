<?php

namespace Laminas\XmlRpc\Server;

use Laminas\Server\Cache as BaseCache;
use Laminas\Server\Definition;
use Laminas\Server\Server;
use Laminas\Server\ServerInterface;
use Laminas\Stdlib\ErrorHandler;

use function array_keys;
use function dirname;
use function file_exists;
use function file_put_contents;
use function in_array;
use function is_writable;
use function serialize;

/**
 * Laminas\XmlRpc\Server\Cache: cache Laminas\XmlRpc\Server server definition
 */
final class Cache
{
    /** @var array Skip system methods when caching XML-RPC server */
    protected static array $skipMethods = [
        'system.listMethods',
        'system.methodHelp',
        'system.methodSignature',
        'system.multicall',
    ];

    public static function save(string $filename, ServerInterface|Server $server): bool
    {
        if (! file_exists($filename) && ! is_writable(dirname($filename))) {
            return false;
        }

        $methods    = $server->getFunctions();
        $definition = self::createDefinition($methods);

        ErrorHandler::start();
        $test = file_put_contents($filename, serialize($definition));
        ErrorHandler::stop();

        return $test !== 0;
    }

    public static function get(string $filename, ServerInterface|Server $server): bool
    {
        return BaseCache::get($filename, $server);
    }

    public static function delete(string $filename): bool
    {
        return BaseCache::delete($filename);
    }

    private static function createDefinition(array|Definition $methods): array|Definition
    {
        if ($methods instanceof Definition) {
            $definition = new Definition();
            foreach ($methods as $method) {
                if (in_array($method->getName(), self::$skipMethods, true)) {
                    continue;
                }
                $definition->addMethod($method);
            }
            return $definition;
        }

        foreach (array_keys($methods) as $methodName) {
            if (in_array($methodName, self::$skipMethods, true)) {
                unset($methods[$methodName]);
            }
        }
        return $methods;
    }
}
