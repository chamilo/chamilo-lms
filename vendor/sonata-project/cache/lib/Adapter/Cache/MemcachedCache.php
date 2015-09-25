<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Cache;

use Sonata\Cache\CacheElement;

class MemcachedCache extends BaseCacheHandler
{
    protected $servers;

    protected $prefix;

    protected $collection;

    /**
     * @param $prefix
     * @param array $servers
     */
    public function __construct($prefix, array $servers)
    {
        $this->prefix  = $prefix;
        $this->servers = $servers;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->getCollection()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array())
    {
        return $this->getCollection()->delete($this->computeCacheKeys($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        return $this->getCollection()->get($this->computeCacheKeys($keys)) !== false;
    }

    /**
     * {@inheritdoc}
     */
    private function getCollection()
    {
        if (!$this->collection) {
            $this->collection = new \Memcached();

            foreach ($this->servers as $server) {
                $this->collection->addServer($server['host'], $server['port'], $server['weight']);
            }
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = CacheElement::DAY, array $contextualKeys = array())
    {
        $cacheElement = new CacheElement($keys, $data, $ttl);

        $this->getCollection()->set(
            $this->computeCacheKeys($keys),
            $cacheElement,
            /**
             * The driver does not seems to behave as documented, so we provide a timestamp if the ttl > 30d
             *   http://code.google.com/p/memcached/wiki/NewProgramming#Cache_Invalidation
             */
            $cacheElement->getTtl() + ($cacheElement->getTtl() > 2592000 ? time() : 0)
        );

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    private function computeCacheKeys(array $keys)
    {
        ksort($keys);

        return md5($this->prefix.serialize($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        return $this->handleGet($keys, $this->getCollection()->get($this->computeCacheKeys($keys)));
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}
