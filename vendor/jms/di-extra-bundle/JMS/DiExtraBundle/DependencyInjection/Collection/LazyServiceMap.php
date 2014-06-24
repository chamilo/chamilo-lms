<?php

namespace JMS\DiExtraBundle\DependencyInjection\Collection;

use PhpCollection\Map;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A map of services which may be initialized lazily.
 *
 * This is useful if you have a list of services which implement a common interface, and where you only need selected
 * services during a request. The map then automatically lazily initializes these services upon first access.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LazyServiceMap extends Map
{
    private $container;
    private $serviceIds;

    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container = $container;
        $this->serviceIds = $serviceIds;
    }

    public function get($key)
    {
        $this->initialize($key);

        return parent::get($key);
    }
    
    public function containsKey($key)
    {
        return isset($this->serviceIds[$key]) || parent::containsKey($key);
    }

    public function remove($key)
    {
        $this->initialize($key);

        return parent::remove($key);
    }

    public function getIterator()
    {
        foreach ($this->serviceIds as $k => $id) {
            $this->set($k, $this->container->get($this->serviceIds[$id]));
            unset($this->serviceIds[$k]);
        }

        return parent::getIterator();
    }

    private function initialize($key)
    {
        if ( ! isset($this->serviceIds[$key])) {
            return;
        }

        $this->set($key, $this->container->get($this->serviceIds[$key]));
        unset($this->serviceIds[$key]);
    }
}
