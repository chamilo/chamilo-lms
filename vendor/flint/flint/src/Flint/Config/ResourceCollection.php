<?php

namespace Flint\Config;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Collection of Resources that is used to burst the cache for the configs
 * when in debug.
 *
 * @package Flint
 */
class ResourceCollection
{
    protected $resources = array();

    /**
     * @param array $resources
     */
    public function __construct(array $resources = array())
    {
        array_map(array($this, 'add'), $resources);
    }

    /**
     * @param ResourceInterface $resource
     */
    public function add(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }

    /**
     * @return ResourceInterface[]
     */
    public function all()
    {
        return $this->resources;
    }
}
