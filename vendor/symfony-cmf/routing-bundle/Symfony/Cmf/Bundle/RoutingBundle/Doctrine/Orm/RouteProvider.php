<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\DoctrineProvider;

/**
 * Provider loading routes from Doctrine
 *
 * This is <strong>NOT</strong> not a doctrine repository but just the route
 * provider for the NestedMatcher. (you could of course implement this
 * interface in a repository class, if you need that)
 *
 * @author david.buchmann@liip.ch
 */
class RouteProvider extends DoctrineProvider implements RouteProviderInterface
{
    /**
     * @var CandidatesInterface
     */
    private $candidatesStrategy;

    public function __construct(ManagerRegistry $managerRegistry, CandidatesInterface $candidatesStrategy, $className)
    {
        parent::__construct($managerRegistry, $className);
        $this->candidatesStrategy = $candidatesStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();

        $candidates = $this->candidatesStrategy->getCandidates($request);
        if (empty($candidates)) {
            return $collection;
        }
        $routes = $this->getRouteRepository()->findByStaticPrefix($candidates, array('position' => 'ASC'));
        /** @var $route Route */
        foreach ($routes as $route) {
            $collection->add($route->getName(), $route);
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteByName($name)
    {
        if (!$this->candidatesStrategy->isCandidate($name)) {
            throw new RouteNotFoundException(sprintf('Route "%s" is not handled by this route provider', $name));
        }

        $route = $this->getRouteRepository()->findOneBy(array('name' => $name));
        if (!$route) {
            throw new RouteNotFoundException("No route found for name '$name'");
        }

        return $route;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutesByNames($names = null)
    {
        if (null === $names) {
            if (0 === $this->routeCollectionLimit) {
                return array();
            }

            return $this->getRouteRepository()->findBy(array(), null, $this->routeCollectionLimit ?: null);
        }

        $routes = array();
        foreach ($names as $name) {
            // TODO: if we do findByName with multivalue, we need to filter with isCandidate afterwards
            try {
                $routes[] = $this->getRouteByName($name);
            } catch (RouteNotFoundException $e) {
                // not found
            }
        }

        return $routes;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRouteRepository()
    {
        return $this->getObjectManager()->getRepository($this->className);
    }
}
