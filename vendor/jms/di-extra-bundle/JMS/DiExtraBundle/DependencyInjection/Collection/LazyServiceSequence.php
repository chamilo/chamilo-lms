<?php

namespace JMS\DiExtraBundle\DependencyInjection\Collection;

use PhpCollection\Sequence;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyServiceSequence extends Sequence
{
    private $container;

    public function __construct(ContainerInterface $container, array $serviceIds = array())
    {
        parent::__construct($serviceIds);

        $this->container = $container;
    }

    public function get($index)
    {
        $this->initialize($index);

        return parent::get($index);
    }

    public function getIterator()
    {
        return new LazySequenceIterator($this->container, $this, $this->elements);
    }

    private function initialize($index)
    {
        if ( ! isset($this->elements[$index]) || ! is_string($this->elements[$index])) {
            return;
        }

        $this->elements[$index] = $this->container->get($this->elements[$index]);
    }
}