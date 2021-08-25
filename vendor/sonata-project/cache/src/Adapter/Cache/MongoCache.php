<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Cache;

use Sonata\Cache\CacheElement;
use Sonata\Cache\CacheElementInterface;

class MongoCache extends BaseCacheHandler
{
    protected $collection;
    private $servers;

    private $databaseName;

    private $collectionName;

    /**
     * @param array $servers
     * @param $database
     * @param $collection
     */
    public function __construct(array $servers, string $database, string $collection)
    {
        $this->servers = $servers;
        $this->databaseName = $database;
        $this->collectionName = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(): bool
    {
        return $this->flush([]);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = []): bool
    {
        $result = $this->getCollection()->remove($keys, [
            'w' => 1,
        ]);

        return 1 == $result['ok'] && null === $result['err'];
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys): bool
    {
        $keys['_timeout'] = ['$gt' => time()];

        return $this->getCollection()->count($keys) > 0;
    }

    /**
     * Returns the valid Mongo class client for the current php driver.
     *
     * @return string
     */
    public static function getMongoClass()
    {
        if (class_exists('\MongoClient')) {
            return '\MongoClient';
        }

        return '\Mongo';
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, int $ttl = CacheElement::DAY, array $contextualKeys = []): CacheElementInterface
    {
        $time = time();

        $cacheElement = new CacheElement($keys, $data, $ttl, $contextualKeys);

        $keys = $cacheElement->getContextualKeys() + $cacheElement->getKeys();
        $keys['_value'] = new \MongoBinData(serialize($cacheElement), \MongoBinData::BYTE_ARRAY);
        $keys['_updated_at'] = $time;
        $keys['_timeout'] = $time + $cacheElement->getTtl();

        $this->getCollection()->save($keys);

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys): CacheElementInterface
    {
        $record = $this->getRecord($keys);

        return $this->handleGet($keys, $record ? unserialize($record['_value']->bin) : null);
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual(): bool
    {
        return true;
    }

    /**
     * @return \MongoCollection
     */
    private function getCollection(): \MongoCollection
    {
        if (!$this->collection) {
            $class = self::getMongoClass();

            $mongo = new $class(sprintf('mongodb://%s', implode(',', $this->servers)));

            $this->collection = $mongo
                ->selectDB($this->databaseName)
                ->selectCollection($this->collectionName);
        }

        return $this->collection;
    }

    /**
     * @param array $keys
     *
     * @return array|null
     */
    private function getRecord(array $keys)
    {
        $keys['_timeout'] = ['$gt' => time()];

        $results = $this->getCollection()->find($keys);

        if ($results->hasNext()) {
            return $results->getNext();
        }

        return;
    }
}
