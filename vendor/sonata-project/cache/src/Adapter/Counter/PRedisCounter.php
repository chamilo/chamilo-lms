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

use Predis\Client;
use Sonata\Cache\Counter;

class PRedisCounter extends BaseCounter
{
    protected $options;

    protected $parameters;

    protected $client;

    /**
     * @param array $parameters
     * @param array $options
     */
    public function __construct(array $parameters = [], array $options = [])
    {
        $this->parameters = $parameters;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(Counter $counter, int $number = 1): Counter
    {
        $counter = $this->transform($counter);

        if (null === $this->getClient()->get($counter->getName())) {
            $this->getClient()->set($counter->getName(), $value = $counter->getValue() + $number);
        } else {
            $value = $this->getClient()->incrby($counter->getName(), $number);
        }

        return Counter::create($counter->getName(), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(Counter $counter, int $number = 1): Counter
    {
        $counter = $this->transform($counter);

        if (null === $this->getClient()->get($counter->getName())) {
            $this->getClient()->set($counter->getName(), $value = $counter->getValue() - $number);
        } else {
            $value = $this->getClient()->decrby($counter->getName(), $number);
        }

        return Counter::create($counter->getName(), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function set(Counter $counter): Counter
    {
        $this->getClient()->set($counter->getName(), $counter->getValue());

        return $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): Counter
    {
        return Counter::create($name, (int) $this->getClient()->get($name));
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client($this->parameters, $this->options);
        }

        return $this->client;
    }
}
