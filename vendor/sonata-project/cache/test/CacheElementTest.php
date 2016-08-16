<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Tests\Cache;

use Sonata\Cache\CacheElement;

class CacheElementTest extends \PHPUnit_Framework_TestCase
{
    public function testCache()
    {
        $cacheKeys = array(
          'block_id' => '1',
        );

        $cache = new CacheElement($cacheKeys, 'data', 20);

        $this->assertEquals(20, $cache->getTtl());
        $this->assertEquals($cacheKeys, $cache->getKeys());
        $this->assertFalse($cache->isExpired());

        $cache = new CacheElement($cacheKeys, 'data', -1);
        $this->assertTrue($cache->isExpired());

        $this->assertEquals('data', $cache->getData());

        $cache->getExpirationDate();
    }

    public function testContextual()
    {
        $cacheKeys = array(
          'block_id' => '1',
        );

        $cache = new CacheElement($cacheKeys, 'data', CacheElement::DAY, array('foo' => 'bar'));

        $this->assertEquals(array('foo' => 'bar'), $cache->getContextualKeys());
    }
}
