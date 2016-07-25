<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Tests\Adapter\Cache;

use Predis\Client;
use Sonata\Cache\Adapter\Cache\PRedisCache;

class PRedisCacheTest extends BaseTest
{
    public function setUp()
    {
        if (!class_exists('\Predis\Client', true)) {
            $this->markTestSkipped('PRedis is not installed');
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // setup the default timeout (avoid max execution time)
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));

        $result = @socket_connect($socket, '127.0.0.1', 6379);

        if (!$result) {
            $this->markTestSkipped('Redis is not running');
        }

        socket_close($socket);

        $client = new Client(array(
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 42,
        ));

        $client->flushdb();
    }

    /**
     * @return PRedisCache
     */
    public function getCache()
    {
        return new PRedisCache(array(
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 42,
        ));
    }

    /**
     * Tests the flushAll method when connection is a single one.
     */
    public function testFlushAllForSingleConnection()
    {
        $cache = $this->getMockBuilder('Sonata\Cache\Adapter\Cache\PRedisCache')
            ->setMethods(array('getClient'))
            ->getMock();

        $command = $this->getMock('Predis\Command\CommandInterface');

        $client = $this->getMock('Predis\ClientInterface');
        $client->expects($this->exactly(2))->method('createCommand')->with($this->equalTo('flushdb'))->will($this->returnValue($command));
        $client->expects($this->exactly(2))->method('getConnection');
        $client->expects($this->exactly(2))->method('executeCommand')->with($this->equalTo($command))->will($this->onConsecutiveCalls(false, true));

        $cache->expects($this->exactly(6))->method('getClient')->will($this->returnValue($client));

        $this->assertFalse($cache->flushAll());
        $this->assertTrue($cache->flushAll());
    }

    /**
     * Tests the flushAll method when connection is a cluster one.
     */
    public function testFlushAllForClusterConnection()
    {
        $cache = $this->getMockBuilder('Sonata\Cache\Adapter\Cache\PRedisCache')
            ->setMethods(array('getClient'))
            ->getMock();

        $command = $this->getMock('Predis\Command\CommandInterface');

        $connection = $this->getMock('Predis\Connection\PredisCluster');
        $connection->expects($this->exactly(5))->method('executeCommandOnNodes')->with($this->equalTo($command))->will($this->onConsecutiveCalls(array(false), array(true), array(false, true), array(true, false), array(true, true)));

        $client = $this->getMock('Predis\ClientInterface');
        $client->expects($this->exactly(5))->method('createCommand')->with($this->equalTo('flushdb'))->will($this->returnValue($command));
        $client->expects($this->exactly(5))->method('getConnection')->will($this->returnValue($connection));
        $client->expects($this->never())->method('executeCommand');

        $cache->expects($this->exactly(10))->method('getClient')->will($this->returnValue($client));

        $this->assertFalse($cache->flushAll());
        $this->assertTrue($cache->flushAll());
        $this->assertFalse($cache->flushAll());
        $this->assertFalse($cache->flushAll());
        $this->assertTrue($cache->flushAll());
    }
}
