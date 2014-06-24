<?php

namespace JMS\DiExtraBundle\Tests\DependencyInjection\Collection;

use JMS\DiExtraBundle\DependencyInjection\Collection\LazyServiceMap;

class LazyServiceMapTest extends \PHPUnit_Framework_TestCase
{
    private $map;
    private $container;

    public function testGet()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('bar')
            ->will($this->returnValue($a = new \stdClass));

        $this->assertSame($a, $this->map->get('foo')->get());
        $this->assertSame($a, $this->map->get('foo')->get());
    }

    public function testRemove()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with('bar')
            ->will($this->returnValue($a = new \stdClass));

        $this->assertSame($a, $this->map->remove('foo'));
        $this->assertFalse($this->map->contains($a));
        $this->assertFalse($this->map->containsKey('foo'));
    }

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->map = new LazyServiceMap($this->container, array(
            'foo' => 'bar',
            'bar' => 'baz',
        ));
    }
}