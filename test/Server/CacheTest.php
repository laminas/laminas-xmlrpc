<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\Server;

use Laminas\XmlRpc\Server;
use Laminas\XmlRpc\Server\Cache;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_put_contents;
use function is_writable;
use function realpath;
use function unlink;

final class CacheTest extends TestCase
{
    /**
     * Server object
     */
    protected Server $server;

    /**
     * Local file for caching
     */
    protected string $file;

    /**
     * Setup environment
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->file   = realpath(__DIR__) . '/xmlrpc.cache';
        $this->server = new Server();
        $this->server->setClass(Cache::class, 'cache');
    }

    /**
     * Teardown environment
     */
    #[\Override]
    protected function tearDown(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        unset($this->server);
    }

    /**
     * Tests functionality of both get() and save()
     */
    public function testGetSave(): void
    {
        if (! is_writable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        $this->assertTrue(Server\Cache::save($this->file, $this->server));
        $expected = $this->server->listMethods();
        $server   = new Server();
        $this->assertTrue(Server\Cache::get($this->file, $server));
        $actual = $server->listMethods();

        $this->assertSame($expected, $actual);
    }

    /**
     * Laminas\XmlRpc\Server\Cache::delete() test
     */
    public function testDelete(): void
    {
        if (! is_writable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        $this->assertTrue(Server\Cache::save($this->file, $this->server));
        $this->assertTrue(Server\Cache::delete($this->file));
    }

    public function testShouldReturnFalseWithInvalidCache(): void
    {
        if (! is_writable('./')) {
            $this->markTestIncomplete('Directory no writable');
        }

        file_put_contents($this->file, 'blahblahblah');
        $server = new Server();
        $this->assertFalse(Server\Cache::get($this->file, $server));
    }
}
