<?php

namespace Tacker\Loader;

use Symfony\Component\Yaml\Yaml;

/**
 * @package Tacker
 */
class YamlFileLoader extends AbstractLoader
{
    /**
     * @param  $resource
     * @return array
     */
    protected function read($resource)
    {
        return Yaml::parse($resource);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
