<?php

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Collection;

use JMS\DiExtraBundle\DependencyInjection\Collection\LazyServiceSequence;

class LazyServiceSequenceTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $seq;

    public function testPartialIteration()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($foo = new \stdClass));

        foreach ($this->seq as $service) {
            $this->assertSame($foo, $service);
            break;
        }

        foreach ($this->seq as $service) {
            $this->assertSame($foo, $service);
            break;
        }
    }

    public function testFullIteration()
    {
        $this->container->expects($this->at(0))
            ->method('get')
            ->with('foo')
            ->will($this->returnValue('service.foo'));
        $this->container->expects($this->at(1))
            ->method('get')
            ->with('bar')
            ->will($this->returnValue('service.bar'));
        $this->container->expects($this->at(2))
            ->method('get')
            ->with('baz')
            ->will($this->returnValue('service.baz'));

        $services = iterator_to_array($this->seq);
        $this->assertSame(array('service.foo', 'service.bar', 'service.baz'), $services);
    }

    public function testGet()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('baz')
            ->will($this->returnValue($baz = new \stdClass));

        $this->assertEquals($baz, $this->seq->get(2));
        $this->assertEquals($baz, $this->seq->get(2));
    }

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->seq = new LazyServiceSequence($this->container, array('foo', 'bar', 'baz'));
    }
}