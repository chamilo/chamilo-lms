<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Counter;

use Sonata\Cache\Adapter\Cache\MongoCache;
use Sonata\Cache\Counter;

class MongoCounter extends BaseCounter
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
     * @return \MongoCollection
     */
    private function getCollection()
    {
        if (!$this->collection) {
            $class = MongoCache::getMongoClass();

            $mongo = new $class(sprintf('mongodb://%s', implode(',', $this->servers)));

            $this->collection = $mongo
                ->selectDB($this->databaseName)
                ->selectCollection($this->collectionName);
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function increment($counter, $number = 1)
    {
        $counter = $this->transform($counter);

        $result = $this->getCollection()->findAndModify(
            array('counter' => $counter->getName()),
            array('$inc'    => array('value' => $number)),
            array(),
            array('new'     => true)
        );

        return $this->handleIncrement(count($result) === 0 ? false : $result['value'], $counter, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($counter, $number = 1)
    {
        $counter = $this->transform($counter);

        $result = $this->getCollection()->findAndModify(
            array('counter' => $counter->getName()),
            array('$inc'    => array('value' => -1 * $number)),
            array(),
            array('new'     => true)
        );

        return $this->handleDecrement(count($result) === 0 ? false : $result['value'], $counter, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function set(Counter $counter)
    {
        $result = $this->getCollection()->findAndModify(
            array('counter'      => $counter->getName()),
            array('$setOnInsert' => array('value' => $counter->getValue())),
            array(),
            array('upsert' => true, 'new' => true)
        );

        return Counter::create($counter->getName(), $result['value']);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        $result = $this->getCollection()->findOne(array('counter' => $name));

        return Counter::create($name, $result ? (int) $result['value'] : 0);
    }
}
