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

use Sonata\Cache\Adapter\Cache\MongoCache;

class MongoCacheTest extends BaseTest
{
    public function setUp()
    {
        $class = MongoCache::getMongoClass();

        if (!class_exists($class, true)) {
            $this->markTestSkipped('Mongo is not installed');
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // setup the default timeout (avoid max execution time)
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));

        $result = @socket_connect($socket, '127.0.0.1', 27017);

        socket_close($socket);

        if (!$result) {
            $this->markTestSkipped('MongoDB is not running');
        }

        $mongo = new $class('mongodb://127.0.0.1:27017');

        $mongo
            ->selectDB('sonata_counter_test')
            ->selectCollection('counter')
            ->remove(array());
    }

    /**
     * @return MongoCache
     */
    public function getCache()
    {
        return new MongoCache(array('127.0.0.1:27017'), 'sonata_cache_test', 'cache');
    }
}
