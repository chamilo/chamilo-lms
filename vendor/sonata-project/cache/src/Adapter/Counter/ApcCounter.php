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

/**
 * Handles APC cache.
 */
class ApcCounter extends BaseCounter
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * Constructor.
     *
     * @param string $prefix A prefix to avoid clash between instances
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(Counter $counter, int $number = 1): Counter
    {
        $counter = $this->transform($counter);

        $value = apc_inc($this->prefix.'/'.$counter->getName(), $number);

        return $this->handleIncrement($value, $counter, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(Counter $counter, int $number = 1): Counter
    {
        $counter = $this->transform($counter);

        $value = apc_dec($this->prefix.'/'.$counter->getName(), $number);

        return $this->handleDecrement($value, $counter, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function set(Counter $counter): Counter
    {
        apc_store($this->prefix.'/'.$counter->getName(), $counter->getValue());

        return $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): Counter
    {
        return Counter::create($name, (int) apc_fetch($this->prefix.'/'.$name));
    }
}
