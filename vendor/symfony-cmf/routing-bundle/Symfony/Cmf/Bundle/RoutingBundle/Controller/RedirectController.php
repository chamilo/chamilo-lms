<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Controller;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Cmf\Component\Routing\RedirectRouteInterface;

/**
 * Default router that handles redirection route objects.
 *
 * This is partially a duplication of Symfony\Bundle\FrameworkBundle\Controller\RedirectController
 * but we do not want a dependency on SymfonyFrameworkBundle just for this.
 *
 * The plus side is that with the route interface we do not need to pass the
 * parameters through magic request attributes.
 */
class RedirectController
{
    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @param RouterInterface $router the router to use to build urls
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Action to redirect based on a RedirectRouteInterface route
     *
     * @param RedirectRouteInterface $contentDocument
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse the response
     */
    public function redirectAction(RedirectRouteInterface $contentDocument)
    {
        $url = $contentDocument->getUri();

        if (empty($url)) {
            $routeTarget = $contentDocument->getRouteTarget();
            if ($routeTarget) {
                $url = $this->router->generate($routeTarget, $contentDocument->getParameters(), true);
            } else {
                $routeName = $contentDocument->getRouteName();
                $url = $this->router->generate($routeName, $contentDocument->getParameters(), true);
            }
        }

        return new RedirectResponse($url, $contentDocument->isPermanent() ? 301 : 302);
    }
}
