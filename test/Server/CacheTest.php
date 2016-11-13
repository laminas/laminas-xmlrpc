<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\XmlRpc\Server;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\XmlRpc\Server;

class CacheTest extends TestCase
{
    /**
     * Server object
     * @var Server
     */
    protected $server;

    /**
     * Local file for caching
     * @var string
     */
    protected $file;

    /**
     * Setup environment
     */
    public function setUp()
    {
        $this->file = realpath(__DIR__) . '/xmlrpc.cache';
        $this->server = new Server();
        $this->server->setClass('Zend\\XmlRpc\\Server\\Cache', 'cache');
    }

    /**
     * Teardown environment
     */
    public function tearDown()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        unset($this->server);
    }

    /**
     * Tests functionality of both get() and save()
     */
    public function testGetSave()
    {
        if (! is_writeable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        $this->assertTrue(Server\Cache::save($this->file, $this->server));
        $expected = $this->server->listMethods();
        $server = new Server();
        $this->assertTrue(Server\Cache::get($this->file, $server));
        $actual = $server->listMethods();

        $this->assertSame($expected, $actual);
    }

    /**
     * Zend\XmlRpc\Server\Cache::delete() test
     */
    public function testDelete()
    {
        if (! is_writeable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        $this->assertTrue(Server\Cache::save($this->file, $this->server));
        $this->assertTrue(Server\Cache::delete($this->file));
    }

    public function testShouldReturnFalseWithInvalidCache()
    {
        if (! is_writeable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        file_put_contents($this->file, 'blahblahblah');
        $server = new Server();
        $this->assertFalse(Server\Cache::get($this->file, $server));
    }
}
