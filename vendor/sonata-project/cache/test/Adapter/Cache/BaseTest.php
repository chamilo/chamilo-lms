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

use Sonata\Cache\CacheAdapterInterface;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return CacheAdapterInterface
     */
    abstract function getCache();

    public function testBasicOperations()
    {
        // init cache
        $cache = $this->getCache();
        $cacheElement = $cache->set(array('id' => 7), 'data');
        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);
        $this->assertTrue($cache->has(array('id' => 7)));

        // test flush
        $cache->set(array('id' => 42), 'data');
        $this->assertTrue($cache->has(array('id' => 42)));

        $res = $cache->flush(array('id' => 42));
        $this->assertTrue(true === $res); // make sure it's really boolean TRUE
        $this->assertFalse($cache->has(array('id' => 42)));

        $cacheElement = $cache->get(array('id' => 7));
        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        // test flush all
        $res = $cache->flushAll();
        $this->assertTrue(true === $res); // make sure it's really boolean TRUE

        $this->assertFalse($cache->has(array('id' => 7)));
    }

    public function testNonExistantCache()
    {
        $cache = $this->getCache();

        $cacheElement = $cache->get(array("invalid"));

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);
        $this->assertTrue($cacheElement->isExpired());
    }

    public function testExpired()
    {
        $cache = $this->getCache();

        $cache->set(array('expired'), "hello", 1);

        sleep(2);

        $cacheElement = $cache->get(array('mykey'));

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);
        $this->assertTrue($cacheElement->isExpired());
        $this->assertNull($cacheElement->getData());
    }
}
