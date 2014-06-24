<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Routing;

use Symfony\Cmf\Component\Routing\Event\Events;
use Symfony\Cmf\Component\Routing\Event\RouterMatchEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class DynamicRouterTest extends CmfUnitTestCase
{
    protected $matcher;
    protected $generator;
    /** @var DynamicRouter */
    protected $router;
    protected $context;
    /** @var Request */
    protected $request;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    protected $container;

    public function setUp()
    {
        $this->matcher = $this->buildMock('Symfony\\Component\\Routing\\Matcher\\UrlMatcherInterface');
        $this->matcher->expects($this->once())
            ->method('match')
            ->with('/foo')
            ->will($this->returnValue(array('foo' => 'bar', RouteObjectInterface::CONTENT_OBJECT => 'bla', RouteObjectInterface::TEMPLATE_NAME => 'template')))
        ;

        $this->generator = $this->buildMock('Symfony\\Component\\Routing\\Generator\\UrlGeneratorInterface');

        $this->request = Request::create('/foo');
        $this->context = $this->buildMock('Symfony\\Component\\Routing\\RequestContext');
        $this->eventDispatcher = $this->buildMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->router = new DynamicRouter($this->context, $this->matcher, $this->generator, '', $this->eventDispatcher);
        $this->router->setRequest($this->request);
    }

    private function assertRequestAttributes($request)
    {
        $this->assertTrue($request->attributes->has(DynamicRouter::CONTENT_KEY));
        $this->assertEquals('bla', $request->attributes->get(DynamicRouter::CONTENT_KEY));
        $this->assertTrue($request->attributes->has(DynamicRouter::CONTENT_TEMPLATE));
        $this->assertEquals('template', $request->attributes->get(DynamicRouter::CONTENT_TEMPLATE));
    }

    public function testMatch()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::PRE_DYNAMIC_MATCH, $this->equalTo(new RouterMatchEvent()))
        ;

        $parameters = $this->router->match('/foo');
        $this->assertEquals(array('foo' => 'bar'), $parameters);

        $this->assertRequestAttributes($this->request);
    }

    public function testMatchRequest()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::PRE_DYNAMIC_MATCH_REQUEST, $this->equalTo(new RouterMatchEvent($this->request)))
        ;

        $parameters = $this->router->matchRequest($this->request);
        $this->assertEquals(array('foo' => 'bar'), $parameters);

        $this->assertRequestAttributes($this->request);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchNoRequest()
    {
        $this->router->setRequest(null);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::PRE_DYNAMIC_MATCH, $this->equalTo(new RouterMatchEvent()))
        ;

        $this->router->match('/foo');
    }

    public function testEventOptional()
    {
        $router = new DynamicRouter($this->context, $this->matcher, $this->generator);

        $parameters = $router->matchRequest($this->request);
        $this->assertEquals(array('foo' => 'bar'), $parameters);

        $this->assertRequestAttributes($this->request);
    }

}
