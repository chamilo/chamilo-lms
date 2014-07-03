<?php

namespace JMS\DiExtraBundle\DependencyInjection\Collection;

use ArrayIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazySequenceIterator extends ArrayIterator
{
    private $container;
    private $seq;

    public function __construct(ContainerInterface $container, LazyServiceSequence $seq, array $elements)
    {
        parent::__construct($elements);

        $this->container = $container;
        $this->seq = $seq;
    }

    public function current()
    {
        $elem = parent::current();

        if (is_string($elem)) {
            $service = $this->container->get($elem);
            $this->seq->update($this->key(), $service);

            return $service;
        }

        return $elem;
    }
}