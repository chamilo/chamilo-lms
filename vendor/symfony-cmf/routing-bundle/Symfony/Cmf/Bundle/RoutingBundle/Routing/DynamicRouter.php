<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Routing;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Symfony\Cmf\Component\Routing\DynamicRouter as BaseDynamicRouter;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Symfony framework integration of the CMF routing component DynamicRouter class
 *
 * @author Filippo de Santis
 * @author David Buchmann
 * @author Lukas Smith
 * @author Nacho Martìn
 */
class DynamicRouter extends BaseDynamicRouter
{
    /**
     * key for the request attribute that contains the route document
     */
    const ROUTE_KEY = 'routeDocument';

    /**
     * key for the request attribute that contains the content document if this
     * route has one associated
     */
    const CONTENT_KEY = 'contentDocument';

    /**
     * key for the request attribute that contains the template this document
     * wants to use
     */
    const CONTENT_TEMPLATE = 'contentTemplate';

    /**
     * @var Request
     */
    protected $request;

    /**
     * Put content and template name into the request attributes instead of the
     * route defaults.
     *
     * {@inheritDoc}
     *
     * The match should identify  a controller for symfony. This can either be
     * the fully qualified class name or the service name of a controller that
     * is registered as a service. In both cases, the action to call on that
     * controller is appended, separated with two colons.
     */
    public function match($url)
    {
        $defaults = parent::match($url);

        return $this->cleanDefaults($defaults);
    }

    public function matchRequest(Request $request)
    {
        $defaults = parent::matchRequest($request);

        return $this->cleanDefaults($defaults, $request);
    }

    /**
     * Clean up the match data and move some values into the request attributes.
     *
     * @param array   $defaults The defaults from the match
     * @param Request $request  The request object if available
     *
     * @return array the updated defaults to return for this match
     */
    protected function cleanDefaults($defaults, Request $request = null)
    {
        if (null === $request) {
            $request = $this->getRequest();
        }

        if (isset($defaults[RouteObjectInterface::ROUTE_OBJECT])) {
            $request->attributes->set(self::ROUTE_KEY, $defaults[RouteObjectInterface::ROUTE_OBJECT]);
            unset($defaults[RouteObjectInterface::ROUTE_OBJECT]);
        }

        if (isset($defaults[RouteObjectInterface::CONTENT_OBJECT])) {
            $request->attributes->set(self::CONTENT_KEY, $defaults[RouteObjectInterface::CONTENT_OBJECT]);
            unset($defaults[RouteObjectInterface::CONTENT_OBJECT]);
        }

        if (isset($defaults[RouteObjectInterface::TEMPLATE_NAME])) {
            $request->attributes->set(self::CONTENT_TEMPLATE, $defaults[RouteObjectInterface::TEMPLATE_NAME]);
            unset($defaults[RouteObjectInterface::TEMPLATE_NAME]);
        }

        return $defaults;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function getRequest()
    {
        if (null === $this->request) {
            throw new ResourceNotFoundException('Request object not available from container');
        }

        return $this->request;
    }
}
