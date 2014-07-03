<?php

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Compiler;

use JMS\DiExtraBundle\DependencyInjection\Compiler\LazyServiceMapPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LazyServiceMapPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $called = false;
        $self = $this;

        $pass = new LazyServiceMapPass('tag', 'key', function(ContainerBuilder $container, Definition $def) use (&$called, $self) {
            $self->assertFalse($called);
            $called = true;

            $self->assertEquals(new Reference('service_container'), $def->getArgument(0));
            $self->assertEquals(array('json' => 'foo', 'xml' => 'bar', 'atom' => 'bar'), $def->getArgument(1));
        });

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('findTaggedServiceIds'))
            ->getMock();

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('tag')
            ->will($this->returnValue(array('foo' => array(array('key' => 'json')), 'bar' => array(array('key' => 'xml'), array('key' => 'atom')))));

        $pass->process($container);
        $this->assertTrue($called);
    }
}
