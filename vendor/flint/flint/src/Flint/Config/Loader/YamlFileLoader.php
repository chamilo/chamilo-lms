<?php

namespace Flint\Config\Loader;

use Symfony\Component\Yaml\Yaml;

/**
 * @package Flint
 */
class YamlFileLoader extends AbstractLoader
{
    /**
     * @param  $resource
     * @return array
     */
    protected function read($resource)
    {
        return Yaml::parse(file_get_contents($resource));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
