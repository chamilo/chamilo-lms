<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\Doctrine\Phpcr;

use Doctrine\ODM\PHPCR\Document\Generic;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\RouteProvider;

use Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\BaseTestCase;

class RouteProviderTest extends BaseTestCase
{
    const ROUTE_ROOT = '/test/routing';

    /** @var RouteProvider */
    private $repository;

    public function setUp()
    {
        parent::setUp();
        $this->db('PHPCR')->createTestNode();
        $this->createRoute(self::ROUTE_ROOT);
        $this->repository = $this->getContainer()->get('cmf_routing.route_provider');
    }

    private function buildRoutes()
    {
        $root = $this->getDm()->find(null, self::ROUTE_ROOT);

        $route = new Route;
        $route->setPosition($root, 'testroute');
        $route->setDefault('_format', 'html');
        $this->getDm()->persist($route);

        // smuggle a non-route thing into the repository
        $noroute = new Generic;
        $noroute->setParent($route);
        $noroute->setNodename('noroute');
        $this->getDm()->persist($noroute);

        $childroute = new Route;
        $childroute->setPosition($noroute, 'child');
        $childroute->setDefault('_format', 'json');
        $this->getDm()->persist($childroute);

        $this->getDm()->flush();
        $this->getDm()->clear();
    }

    public function testGetRouteCollectionForRequest()
    {
        $this->buildRoutes();

        $routes = $this->repository->getRouteCollectionForRequest(Request::create('/testroute/noroute/child'));
        $this->assertCount(3, $routes);
        $this->assertContainsOnlyInstancesOf('Symfony\Cmf\Component\Routing\RouteObjectInterface', $routes);

        $routes = $routes->all();
        list($key, $child) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT . '/testroute/noroute/child', $key);
        $this->assertEquals('json', $child->getDefault('_format'));
        list($key, $testroute) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT . '/testroute', $key);
        $this->assertEquals('html', $testroute->getDefault('_format'));
        list($key, $root) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT, $key);
        $this->assertNull($root->getDefault('_format'));
    }

    public function testGetRouteCollectionForRequestFormat()
    {
        $this->buildRoutes();

        $routes = $this->repository->getRouteCollectionForRequest(Request::create('/testroute/noroute/child.html'));
        $this->assertCount(3, $routes);
        $this->assertContainsOnlyInstancesOf('Symfony\\Cmf\\Component\\Routing\\RouteObjectInterface', $routes);

        $routes = $routes->all();
        list($key, $child) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT . '/testroute/noroute/child', $key);
        $this->assertEquals('json', $child->getDefault('_format'));
        list($key, $testroute) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT . '/testroute', $key);
        $this->assertEquals('html', $testroute->getDefault('_format'));
        list($key, $root) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT, $key);
        $this->assertEquals(null, $root->getDefault('_format'));
    }

    /**
     * The root route will always be found.
     */
    public function testGetRouteCollectionForRequestNophpcrUrl()
    {
        $collection = $this->repository->getRouteCollectionForRequest(Request::create(':///'));
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);
        $this->assertCount(1, $collection);
        $routes = $collection->all();
        list ($key, $route) = each($routes);
        $this->assertEquals(self::ROUTE_ROOT, $key);
    }

    public function testGetRoutesByNames()
    {
        $this->buildRoutes();

        $routeNames = array(
            self::ROUTE_ROOT . '/testroute/noroute/child',
            self::ROUTE_ROOT . '/testroute/noroute',
            self::ROUTE_ROOT . '/testroute/', // trailing slash is invalid for phpcr
            self::ROUTE_ROOT . '/testroute'
        );

        $routes = $this->repository->getRoutesByNames($routeNames);
        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf('Symfony\Cmf\Component\Routing\RouteObjectInterface', $routes);
    }
}
