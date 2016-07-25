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

use Predis\Client;
use Predis\Connection\PredisCluster;
use Sonata\Cache\CacheElement;

class PRedisCache extends BaseCacheHandler
{
    protected $parameters;

    protected $options;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param array $parameters
     * @param array $options
     */
    public function __construct(array $parameters = array(), array $options = array())
    {
        $this->parameters = $parameters;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        $command    = $this->getClient()->createCommand('flushdb');
        $connection = $this->getClient()->getConnection();

        if ($connection instanceof PredisCluster) {
            foreach ($connection->executeCommandOnNodes($command) as $status) {
                if (!$status) {
                    return false;
                }
            }

            return true;
        }

        return $this->getClient()->executeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array())
    {
        $this->getClient()->del($this->computeCacheKeys($keys));

        // http://redis.io/commands/del
        // it is not possible to know is the command succeed as the del command returns
        // the number of row deleted.
        // we can flush an non existant row

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        return $this->getClient()->exists($this->computeCacheKeys($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = CacheElement::DAY, array $contextualKeys = array())
    {
        $cacheElement = new CacheElement($keys, $data, $ttl);

        $key = $this->computeCacheKeys($keys);

        $this->getClient()->hset($key, 'sonata__data', serialize($cacheElement));

        foreach ($contextualKeys as $name => $value) {
            if (!is_scalar($value)) {
                $value = serialize($value);
            }

            $this->getClient()->hset($key, $name, $value);
        }

        foreach ($keys as $name => $value) {
            if (!is_scalar($value)) {
                $value = serialize($value);
            }

            $this->getClient()->hset($key, $name, $value);
        }

        $this->getClient()->expire($key, $cacheElement->getTtl());

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        return $this->handleGet($keys, unserialize($this->getClient()->hget($this->computeCacheKeys($keys), 'sonata__data')));
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = new Client($this->parameters, $this->options);
        }

        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    private function computeCacheKeys(array $keys)
    {
        ksort($keys);

        return md5(serialize($keys));
    }
}
