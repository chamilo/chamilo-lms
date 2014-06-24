<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\RouteProvider;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;
use Symfony\Component\HttpFoundation\Request;

class RouteProviderTest extends CmfUnitTestCase
{
    /**
     * @var Route|\PHPUnit_Framework_MockObject_MockObject
     */
    private $routeMock;

    /**
     * @var Route|\PHPUnit_Framework_MockObject_MockObject
     */
    private $route2Mock;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $managerRegistryMock;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManagerMock;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectRepositoryMock;

    /**
     * @var CandidatesInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $candidatesMock;

    public function setUp()
    {
        $this->routeMock = $this->buildMock('Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route');
        $this->route2Mock = $this->buildMock('Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\Route');
        $this->objectManagerMock = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->managerRegistryMock = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->objectRepositoryMock = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(array('findByStaticPrefix', 'findOneBy', 'findBy'))
            ->disableOriginalConstructor()
            ->getMock();
        $this->candidatesMock = $this->getMock('Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface');
        $this->candidatesMock
            ->expects($this->any())
            ->method('isCandidate')
            ->will($this->returnValue(true))
        ;

        $this->managerRegistryMock
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->objectManagerMock))
        ;
        $this->objectManagerMock
            ->expects($this->any())
            ->method('getRepository')
            ->with('Route')
            ->will($this->returnValue($this->objectRepositoryMock))
        ;
    }

    public function testGetRouteCollectionForRequest()
    {
        $request = Request::create('/my/path');
        $candidates = array('/my/path', '/my', '/');

        $this->candidatesMock
            ->expects($this->once())
            ->method('getCandidates')
            ->with($request)
            ->will($this->returnValue($candidates))
        ;

        $this->routeMock
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('/my/path'))
        ;
        $this->route2Mock
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('/my'))
        ;
        $objects = array(
            $this->routeMock,
            $this->route2Mock,
        );

        $this->objectRepositoryMock
            ->expects($this->once())
            ->method('findByStaticPrefix')
            ->with($candidates, array('position' => 'ASC'))
            ->will($this->returnValue($objects))
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $this->candidatesMock, 'Route');
        $collection = $routeProvider->getRouteCollectionForRequest($request);
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);
        $this->assertCount(2, $collection);
    }

    public function testGetRouteCollectionForRequestEmpty()
    {
        $request = Request::create('/my/path');

        $this->candidatesMock
            ->expects($this->once())
            ->method('getCandidates')
            ->with($request)
            ->will($this->returnValue(array()))
        ;

        $this->objectRepositoryMock
            ->expects($this->never())
            ->method('findByStaticPrefix')
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $this->candidatesMock, 'Route');
        $collection = $routeProvider->getRouteCollectionForRequest($request);
        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);
        $this->assertCount(0, $collection);
    }

    public function testGetRouteByName()
    {
        $this->objectRepositoryMock
            ->expects($this->any())
            ->method('findOneBy')
            ->with(array('name' => '/test-route'))
            ->will($this->returnValue($this->routeMock))
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $this->candidatesMock, 'Route');
        $routeProvider->setManagerName('default');

        $foundRoute = $routeProvider->getRouteByName('/test-route');

        $this->assertSame($this->routeMock, $foundRoute);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGetRouteByNameNotFound()
    {
        $this->objectRepositoryMock
            ->expects($this->any())
            ->method('findOneBy')
            ->with(array('name' => '/test-route'))
            ->will($this->returnValue(null))
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $this->candidatesMock, 'Route');
        $routeProvider->setManagerName('default');

        $routeProvider->getRouteByName('/test-route');
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGetRouteByNameNotCandidate()
    {
        $this->objectRepositoryMock
            ->expects($this->never())
            ->method('findOneBy')
        ;
        $candidatesMock = $this->getMock('Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface');
        $candidatesMock
            ->expects($this->once())
            ->method('isCandidate')
            ->with('/test-route')
            ->will($this->returnValue(false))
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $candidatesMock, 'Route');
        $routeProvider->setManagerName('default');

        $routeProvider->getRouteByName('/test-route');
    }

    public function testGetRoutesByNames()
    {
        $paths = array(
            '/test-route',
            '/other-route',
        );

        $this->objectRepositoryMock
            ->expects($this->at(0))
            ->method('findOneBy')
            ->with(array('name' => $paths[0]))
            ->will($this->returnValue($this->routeMock))
        ;
        $this->objectRepositoryMock
            ->expects($this->at(1))
            ->method('findOneBy')
            ->with(array('name' => $paths[1]))
            ->will($this->returnValue($this->routeMock))
        ;

        $paths[] = '/no-candidate';

        $candidatesMock = $this->getMock('Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface');
        $candidatesMock
            ->expects($this->at(0))
            ->method('isCandidate')
            ->with($paths[0])
            ->will($this->returnValue(true))
        ;
        $candidatesMock
            ->expects($this->at(1))
            ->method('isCandidate')
            ->with($paths[1])
            ->will($this->returnValue(true))
        ;
        $candidatesMock
            ->expects($this->at(2))
            ->method('isCandidate')
            ->with($paths[2])
            ->will($this->returnValue(false))
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $candidatesMock, 'Route');
        $routeProvider->setManagerName('default');

        $routes = $routeProvider->getRoutesByNames($paths);
        $this->assertCount(2, $routes);
    }

    public function testGetAllRoutesDisabled()
    {
        $this->objectRepositoryMock
            ->expects($this->never())
            ->method('findBy')
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $this->candidatesMock, 'Route');
        $routeProvider->setRouteCollectionLimit(0);
        $routeProvider->setManagerName('default');

        $routes = $routeProvider->getRoutesByNames(null);
        $this->assertCount(0, $routes);
    }

    public function testGetAllRoutes()
    {
        $this->objectRepositoryMock
            ->expects($this->once())
            ->method('findBy')
            ->with(array(), null, 42)
            ->will($this->returnValue(array($this->routeMock)))
        ;

        $routeProvider = new RouteProvider($this->managerRegistryMock, $this->candidatesMock, 'Route');
        $routeProvider->setManagerName('default');
        $routeProvider->setRouteCollectionLimit(42);

        $routes = $routeProvider->getRoutesByNames(null);
        $this->assertCount(1, $routes);
    }
}
