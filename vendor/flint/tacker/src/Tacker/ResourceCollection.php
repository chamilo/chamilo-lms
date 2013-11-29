<?php

namespace Tacker;

use Symfony\Component\Config\Resource\ResourceInterface;

class ResourceCollection
{
    protected $resources = array();

    public function add(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }

    public function all()
    {
        return $this->resources;
    }
}
