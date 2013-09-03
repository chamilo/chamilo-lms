<?php

namespace Flint\Tests\Provider;

use Flint\Provider\RoutingServiceProvider;
use Flint\Application;

class RoutingServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->app = new Application(__DIR__, true);
        $this->provider = new RoutingServiceProvider;
    }

    public function testUrlMatcherAndGeneratorIsAliasOfRouter()
    {
        $this->provider->register($this->app);

        $this->assertInstanceOf('Symfony\Component\Routing\Router', $this->app['url_matcher']);
        $this->assertInstanceOf('Symfony\Component\Routing\Router', $this->app['url_generator']);
    }

    public function testRedirectableUrlMatcherIsUsed()
    {
        $this->provider->register($this->app);

        $this->assertEquals('Silex\\RedirectableUrlMatcher', $this->app['router']->getOption('matcher_class'));
        $this->assertEquals('Silex\\RedirectableUrlMatcher', $this->app['router']->getOption('matcher_base_class'));
    }

    public function testRouteCollectionIsGottenFromRouter()
    {
        $router = $this->getMockBuilder('Symfony\Component\Routing\Router')->disableOriginalConstructor()->getMock();
        $router->expects($this->once())->method('getRouteCollection');

        $this->provider->register($this->app);

        $this->app['router'] = $router;

        $this->app['routes'];
    }
}
