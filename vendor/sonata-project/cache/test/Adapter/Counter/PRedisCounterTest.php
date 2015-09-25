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
use Sonata\Cache\Adapter\Counter\PRedisCounter;
use Sonata\Cache\Counter;

class PRedisCounterTest extends \PHPUnit_Framework_TestCase
{
    protected $parameters = array(
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 42
    );

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

        $client = new Client($this->parameters);

        $client->flushdb();
    }

    public function testCounterBackend()
    {
        $backend = new PRedisCounter($this->parameters);

        $counter = $backend->set(Counter::create('mycounter', 10));

        $this->assertInstanceOf('Sonata\Cache\Counter', $counter);
        $this->assertEquals(10, $counter->getValue());
        $this->assertEquals('mycounter', $counter->getName());

        $counter = $backend->get('mycounter');
        $this->assertInstanceOf('Sonata\Cache\Counter', $counter);
        $this->assertEquals(10, $counter->getValue());
        $this->assertEquals('mycounter', $counter->getName());

        $counter = $backend->increment($counter);
        $this->assertEquals(11, $counter->getValue());

        $counter = $backend->increment($counter, 10);
        $this->assertEquals(21, $counter->getValue());

        $counter = $backend->decrement($counter);
        $this->assertEquals(20, $counter->getValue());

        $counter = $backend->decrement($counter, 30);

        $this->assertEquals(-10, $counter->getValue());
    }

    public function testNonExistantKey()
    {
        $backend = new PRedisCounter($this->parameters);

        $counter = $backend->increment(Counter::create('mynewcounter.inc', 10));

        $this->assertEquals(11, $counter->getValue());

        $counter = $backend->decrement(Counter::create('mynewcounter.dec', 10));

        $this->assertEquals(9, $counter->getValue());
    }
}