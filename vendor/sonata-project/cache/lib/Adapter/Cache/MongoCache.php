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

class MongoCache extends BaseCacheHandler
{
    private $servers;

    private $databaseName;

    private $collectionName;

    protected $collection;

    /**
     * @param array $servers
     * @param $database
     * @param $collection
     */
    public function __construct(array $servers, $database, $collection)
    {
        $this->servers        = $servers;
        $this->databaseName   = $database;
        $this->collectionName = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->flush(array());
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array())
    {
        $result = $this->getCollection()->remove($keys, array(
            'w' => 1
        ));

        return $result['ok'] == 1 && $result['err'] === null;
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        $keys['_timeout'] = array('$gt' => time());

        return $this->getCollection()->count($keys) > 0;
    }

    /**
     * @return \MongoCollection
     */
    private function getCollection()
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
     * Returns the valid Mongo class client for the current php driver
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
    public function set(array $keys, $data, $ttl = CacheElement::DAY, array $contextualKeys = array())
    {
        $time = time();

        $cacheElement = new CacheElement($keys, $data, $ttl, $contextualKeys);

        $keys = $cacheElement->getContextualKeys() + $cacheElement->getKeys();
        $keys['_value']       = new \MongoBinData(serialize($cacheElement), \MongoBinData::BYTE_ARRAY);
        $keys['_updated_at']  = $time;
        $keys['_timeout']     = $time + $cacheElement->getTtl();

        $this->getCollection()->save($keys);

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        $record = $this->getRecord($keys);

        return $this->handleGet($keys, $record ? unserialize($record['_value']->bin) : null);
    }

    /**
     * @param  array      $keys
     * @return array|null
     */
    private function getRecord(array $keys)
    {
        $keys['_timeout'] = array('$gt' => time());

        $results =  $this->getCollection()->find($keys);

        if ($results->hasNext()) {
            return $results->getNext();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return true;
    }
}
