<?php

namespace Tacker\Loader;

/**
 * @package Tacker
 */
class JsonFileLoader extends AbstractLoader
{
    protected function read($resource)
    {
        return json_decode(file_get_contents($resource), true);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'json' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
