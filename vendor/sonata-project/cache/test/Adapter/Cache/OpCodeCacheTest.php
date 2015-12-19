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

use Sonata\Cache\Adapter\Cache\OpCodeCache;

class OpCodeCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OpCodeCache
     */
    protected $cache;

    public function setUp()
    {
        $this->cache = new OpCodeCache('http://localhost', 'prefix_', array(), array());
        $this->cache->setCurrentOnly(true);
    }

    public function testFlushAll()
    {
        $res = $this->cache->flushAll();
        $this->assertTrue($res);
    }
}
