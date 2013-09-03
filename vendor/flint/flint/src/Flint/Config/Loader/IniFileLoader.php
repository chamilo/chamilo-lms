<?php

namespace Flint\Config\Loader;

/**
 * @package Flint
 */
class IniFileLoader extends AbstractLoader
{
    protected function read($resource)
    {
        return parse_ini_string($this->normalizer->normalize(file_get_contents($resource)), true);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'ini' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
