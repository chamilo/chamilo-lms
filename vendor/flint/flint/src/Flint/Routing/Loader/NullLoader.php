<?php

namespace Flint\Routing\Loader;

use Symfony\Component\Routing\RouteCollection;

/**
 * When `routing.resource` is null a loader needs to support that.
 * This just returns an empty RouteCollection so an exception will
 * not be raised and to keep BC with Silex.
 *
 * @package Flint
 */
class NullLoader extends \Symfony\Component\Config\Loader\Loader
{
    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        return new RouteCollection;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return null === $resource;
    }
}
