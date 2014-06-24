<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Model;

use LogicException;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Cmf\Component\Routing\RedirectRouteInterface;

/**
 * {@inheritDoc}
 */
class RedirectRoute extends Route implements RedirectRouteInterface
{
    /**
     * Absolute uri to redirect to.
     */
    protected $uri;

    /**
     * The name of a target route (for use with standard symfony routes).
     */
    protected $routeName;

    /**
     * Target route document to redirect to different dynamic route.
     */
    protected $routeTarget;

    /**
     * Whether this is a permanent redirect. Defaults to false.
     */
    protected $permanent = false;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * Never call this, it makes no sense. The redirect route will return $this
     * as route content for the redirection controller to have the redirect route
     * object as content.
     *
     * @param $document
     *
     * @throws LogicException
     */
    public function setContent($document)
    {
        throw new LogicException('Do not set a content for the redirect route. It is its own content.');
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this;
    }

    /**
     * Set the route this redirection route points to. This must be a PHPCR-ODM
     * mapped object.
     *
     * @param SymfonyRoute $document the redirection target route
     */
    public function setRouteTarget(SymfonyRoute $document)
    {
        $this->routeTarget = $document;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteTarget()
    {
        return $this->routeTarget;
    }

    /**
     * Set a symfony route name for this redirection.
     *
     * @param string $routeName
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Set whether this redirection should be permanent or not. Default is
     * false.
     *
     * @param boolean $permanent if true this is a permanent redirection
     */
    public function setPermanent($permanent)
    {
        $this->permanent = $permanent;
    }

    /**
     * {@inheritDoc}
     */
    public function isPermanent()
    {
        return $this->permanent;
    }

    /**
     * Set the parameters for building this route. Used with both route name
     * and target route document.
     *
     * @param array $parameters a hashmap of key to value mapping for route
     *                          parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set the absolute redirection target URI.
     *
     * @param string $uri the absolute URI
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * {@inheritDoc}
     */
    public function getUri()
    {
        return $this->uri;
    }
}
