<?php

/**
 * @see       https://github.com/laminas/laminas-xmlrpc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-xmlrpc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-xmlrpc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\XmlRpc\Server;

use Laminas\XmlRpc\Server;

/**
 * @group      Laminas_XmlRpc
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Laminas_XmlRpc_Server object
     * @var Laminas_XmlRpc_Server
     */
    protected $_server;

    /**
     * Local file for caching
     * @var string
     */
    protected $_file;

    /**
     * Setup environment
     */
    public function setUp()
    {
        $this->_file = realpath(__DIR__) . '/xmlrpc.cache';
        $this->_server = new Server();
        $this->_server->setClass('Laminas\\XmlRpc\\Server\\Cache', 'cache');
    }

    /**
     * Teardown environment
     */
    public function tearDown()
    {
        if (file_exists($this->_file)) {
            unlink($this->_file);
        }
        unset($this->_server);
    }

    /**
     * Tests functionality of both get() and save()
     */
    public function testGetSave()
    {
        if (!is_writeable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        $this->assertTrue(Server\Cache::save($this->_file, $this->_server));
        $expected = $this->_server->listMethods();
        $server = new Server();
        $this->assertTrue(Server\Cache::get($this->_file, $server));
        $actual = $server->listMethods();

        $this->assertSame($expected, $actual);
    }

    /**
     * Laminas\XmlRpc\Server\Cache::delete() test
     */
    public function testDelete()
    {
        if (!is_writeable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        $this->assertTrue(Server\Cache::save($this->_file, $this->_server));
        $this->assertTrue(Server\Cache::delete($this->_file));
    }

    public function testShouldReturnFalseWithInvalidCache()
    {
        if (!is_writeable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        file_put_contents($this->_file, 'blahblahblah');
        $server = new Server();
        $this->assertFalse(Server\Cache::get($this->_file, $server));
    }
}
