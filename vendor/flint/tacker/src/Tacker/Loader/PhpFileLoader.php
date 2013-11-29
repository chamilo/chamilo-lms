<?php

namespace Tacker\Loader;

class PhpFileLoader extends AbstractLoader
{
    protected function read($resource)
    {
        return require $resource;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'php' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
