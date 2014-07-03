<?php

namespace JMS\DiExtraBundle\Tests\Metadata\Driver;

use Symfony\Component\DependencyInjection\Reference;
use JMS\DiExtraBundle\Metadata\ClassMetadata;
use JMS\DiExtraBundle\Metadata\Driver\ConfiguredControllerInjectionsDriver;

class ConfiguredControllerInjectionsDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testIgnoresNonControllers()
    {
        $class = new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\NonControllerClass');
        $this->delegateReturnsEmptyMetadata();
        $metadata = $this->getDriver(array('foo' => new Reference('foo')))->loadMetadataForClass($class);

        $this->assertArrayNotHasKey('foo', $metadata->properties);
    }

    public function testLoadMetadataForClass()
    {
        $class = new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Controller\MyTestController');
        $this->delegateReturnsEmptyMetadata();
        $metadata = $this->getDriver(array('foo' => $ref = new Reference('foo')),
            array('setFoo' => array('foo')))->loadMetadataForClass($class);

        $this->assertArrayHasKey('foo', $metadata->properties);
        $this->assertSame($ref, $metadata->properties['foo']);

        $this->assertSame('setFoo', $metadata->methodCalls[0][0]);
        $this->assertSame(array('foo'), $metadata->methodCalls[0][1]);
    }

    public function testExplicitConfigurationWins()
    {
        $class = new \ReflectionClass('JMS\DiExtraBundle\Tests\Metadata\Driver\Controller\MyTestController');
        $this->delegate->expects($this->once())
            ->method('loadMetadataForClass')
            ->with($class)
            ->will($this->returnCallback(function() use ($class) {
                $metadata = new ClassMetadata($class->name);
                $metadata->properties['foo'] = new Reference('bar');
                $metadata->methodCalls[] = array('setFoo', array('foo'));

                return $metadata;
            }))
        ;

        $metadata = $this->getDriver(array('foo' => new Reference('baz'), array('setFoo' => array('bar'), 'setBar' => array('bar'))))->loadMetadataForClass($class);
        $this->assertArrayHasKey('foo', $metadata->properties);
        $this->assertEquals('bar', (string) $metadata->properties['foo']);

        $this->assertSame('setFoo', $metadata->methodCalls[0][0]);
        $this->assertEquals(1, count($metadata->methodCalls));
        $this->assertSame(array('foo'), $metadata->methodCalls[0][1]);
    }

    protected function setUp()
    {
        $this->delegate = $this->getMock('Metadata\Driver\DriverInterface');
    }

    private function delegateReturnsEmptyMetadata()
    {
        $this->delegate
            ->expects($this->any())
            ->method('loadMetadataForClass')
            ->will($this->returnCallback(function($v) {
                return new ClassMetadata($v->name);
            }))
        ;
    }

    private function getDriver(array $propertyInjections = array(), array $methodInjections = array())
    {
        return new ConfiguredControllerInjectionsDriver($this->delegate, $propertyInjections, $methodInjections);
    }
}

class NonControllerClass
{
    private $foo;
}

namespace JMS\DiExtraBundle\Tests\Metadata\Driver\Controller;

class MyTestController
{
    private $foo;
    public function setFoo() { }
    private function setBar() { }
}
