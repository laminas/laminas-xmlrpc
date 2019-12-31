<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\XmlRpc\Server;

/**
 * Laminas_XmlRpc_Server_Cache: cache Laminas_XmlRpc_Server server definition
 *
 * @category   Laminas
 * @package    Laminas_XmlRpc
 * @subpackage Server
 */
class Cache extends \Laminas\Server\Cache
{
    /**
     * @var array Skip system methods when caching XML-RPC server
     */
    protected static $skipMethods = array(
        'system.listMethods',
        'system.methodHelp',
        'system.methodSignature',
        'system.multicall',
    );
}
