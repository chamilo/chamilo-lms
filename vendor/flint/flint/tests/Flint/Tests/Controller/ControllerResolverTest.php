<?php

namespace Flint\Tests\Controller;

use Flint\Controller\ControllerResolver;

class ControllerResolverTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
    }

    public function testPimpleIsInjectedWhenControllerIsPimpleAware()
    {
        $pimple = new \Pimple();
        $resolver = new ControllerResolver($this->resolver, $pimple);
        $controller = $this->getMock('Flint\PimpleAwareInterface');

        $controller->expects($this->once())->method('setPimple')->with($this->equalTo($pimple));

        $this->resolver->expects($this->any())->method('getController')->will($this->returnValue(array(
            $controller,
            'indexAction',
        )));

        $resolver->getController($this->getMock('Symfony\Component\HttpFoundation\Request'));
    }

    public function testPimpleIsNotInjectedWhenNotApplicable()
    {
        $controller = $this->getMock('Flint\PimpleAwareInterface');
        $controller->expects($this->never())->method('setPimple');

        $this->resolver->expects($this->any())->method('getController')->will($this->returnValue(null));

        $resolver = new ControllerResolver($this->resolver, new \Pimple());
        $resolver->getController($this->getMock('Symfony\Component\HttpFoundation\Request'));
    }

    public function testGetArgumentsIsDelegatedToWrappedResolver()
    {
        $resolver = new ControllerResolver($this->resolver, new \Pimple());

        $this->resolver->expects($this->once())->method('getArguments');

        $resolver->getArguments($this->getMock('Symfony\Component\HttpFoundation\Request'), array());
    }
}
