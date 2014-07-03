<?php

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Compiler;

use JMS\DiExtraBundle\DependencyInjection\Compiler\LazyServiceSequencePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LazyServiceSequencePassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $called = false;
        $self = $this;

        $pass = new LazyServiceSequencePass('tag', function(ContainerBuilder $container, Definition $def) use (&$called, $self) {
            $self->assertFalse($called);
            $called = true;

            $self->assertEquals(new Reference('service_container'), $def->getArgument(0));
            $self->assertEquals(array('foo', 'bar'), $def->getArgument(1));
        });

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('findTaggedServiceIds'))
            ->getMock();

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('tag')
            ->will($this->returnValue(array('foo' => array(), 'bar' => array())));

        $pass->process($container);
        $this->assertTrue($called);
    }
}