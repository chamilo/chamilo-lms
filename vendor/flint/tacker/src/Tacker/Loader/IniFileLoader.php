<?php

namespace Tacker\Loader;

/**
 * @package Tacker
 */
class IniFileLoader extends AbstractLoader
{
    protected function read($resource)
    {
        return parse_ini_file($resource, true);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'ini' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
