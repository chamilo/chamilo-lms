<?php

namespace Flint\Tests\Provider;

use Flint\Provider\FlintServiceProvider;
use Flint\Application;

class FlintServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->app = new Application(__DIR__, true);
        $this->provider = new FlintServiceProvider;
    }

    public function testResolverIsOverridden()
    {
        $this->provider->register($this->app);

        $this->assertInstanceOf('Flint\Controller\ControllerResolver', $this->app['resolver']);
    }

    public function testCustomExceptionController()
    {
        $this->provider->register($this->app);
        $this->app['exception_controller'] = 'Acme\\Controller\\ExceptionController::showAction';

        $listener = $this->app['exception_handler'];
        $refl = new \ReflectionProperty($listener, 'controller');
        $refl->setAccessible(true);

        $this->assertEquals('Acme\\Controller\\ExceptionController::showAction', $refl->getValue($listener));
    }

    public function testExceptionHandlerIsOverrriden()
    {
        $this->provider->register($this->app);

        $this->assertInstanceOf('Symfony\Component\HttpKernel\EventListener\ExceptionListener', $this->app['exception_handler']);
    }

    public function testTwigFileLoaderFlintNamespacePathIsAdded()
    {
        $refl = new \ReflectionClass('Flint\Provider\FlintServiceProvider');
        $dir = dirname($refl->getFileName()) . '/../Resources/views';

        $loader = $this->getMockBuilder('Twig_Loader_Filesystem')->disableOriginalConstructor()->getMock();
        $loader->expects($this->once())->method('addPath')->with($this->equalTo($dir), $this->equalTo('Flint'));

        $this->app['twig.loader.filesystem'] = $this->app->share(function () use ($loader) {
            return $loader;
        });

        $provider = new FlintServiceProvider;
        $provider->register($this->app);

        $this->app['twig.loader.filesystem'];
    }
}
