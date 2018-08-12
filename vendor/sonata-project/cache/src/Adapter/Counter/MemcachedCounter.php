<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Counter;

use Sonata\Cache\Counter;

class MemcachedCounter extends BaseCounter
{
    protected $servers;

    protected $prefix;

    protected $collection;

    /**
     * @param $prefix
     * @param array $servers
     */
    public function __construct(string $prefix, array $servers)
    {
        $this->prefix = $prefix;
        $this->servers = $servers;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(Counter $counter, int $number = 1): Counter
    {
        $counter = $this->transform($counter);

        $value = $this->getCollection()->increment($this->prefix.'.'.$counter->getName(), $number);

        return $this->handleIncrement(0 !== $this->getCollection()->getResultCode() ? false : $value, $counter, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(Counter $counter, int $number = 1): Counter
    {
        $counter = $this->transform($counter);

        $value = $this->getCollection()->decrement($this->prefix.'.'.$counter->getName(), $number);

        return $this->handleDecrement(0 !== $this->getCollection()->getResultCode() ? false : $value, $counter, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function set(Counter $counter): Counter
    {
        $this->getCollection()->add($this->prefix.'.'.$counter->getName(), $counter->getValue());

        return $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): Counter
    {
        return Counter::create($name, (int) $this->getCollection()->get($this->prefix.'.'.$name));
    }

    /**
     * {@inheritdoc}
     */
    private function getCollection(): \Memcached
    {
        if (!$this->collection) {
            $this->collection = new \Memcached();

            foreach ($this->servers as $server) {
                $this->collection->addServer($server['host'], $server['port'], $server['weight']);
            }
        }

        return $this->collection;
    }
}
