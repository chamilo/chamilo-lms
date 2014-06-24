<?php

namespace Alchemy\Zippy\Tests\Resource;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Resource\TeleporterContainer;

class TeleporterContainerTest extends TestCase
{
    /**
     * @covers Alchemy\Zippy\Resource\TeleporterContainer::fromResource
     * @dataProvider provideResourceData
     */
    public function testFromResource($resource, $classname)
    {
        $container = TeleporterContainer::load();

        $this->assertInstanceOf($classname, $container->fromResource($resource));
    }
    /**
     * @covers Alchemy\Zippy\Resource\TeleporterContainer::fromResource
     * @expectedException Alchemy\Zippy\Exception\InvalidArgumentException
     */
    public function testFromResourceThatFails()
    {
        $container = TeleporterContainer::load();
        $container->fromResource($this->createResource(array()));
    }

    public function provideResourceData()
    {
        return array(
            array($this->createResource(__FILE__), 'Alchemy\Zippy\Resource\Teleporter\LocalTeleporter'),
            array($this->createResource(fopen(__FILE__, 'rb')), 'Alchemy\Zippy\Resource\Teleporter\StreamTeleporter'),
            array($this->createResource('ftp://192.168.1.1/images/elephant.png'), 'Alchemy\Zippy\Resource\Teleporter\StreamTeleporter'),
            array($this->createResource('http://www.google.com/+/business/images/plus-badge.png'), 'Alchemy\Zippy\Resource\Teleporter\GuzzleTeleporter'),
        );
    }

    private function createResource($data)
    {
        $resource = $this->getMockBuilder('Alchemy\Zippy\Resource\Resource')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->any())
            ->method('getOriginal')
            ->will($this->returnValue($data));

        return $resource;
    }

    /**
     * @covers Alchemy\Zippy\Resource\TeleporterContainer::load
     */
    public function testLoad()
    {
        $container = TeleporterContainer::load();

        $this->assertInstanceOf('Alchemy\Zippy\Resource\TeleporterContainer', $container);

        $this->assertInstanceOf('Alchemy\Zippy\Resource\Teleporter\GuzzleTeleporter', $container['guzzle-teleporter']);
        $this->assertInstanceOf('Alchemy\Zippy\Resource\Teleporter\StreamTeleporter', $container['stream-teleporter']);
        $this->assertInstanceOf('Alchemy\Zippy\Resource\Teleporter\LocalTeleporter', $container['local-teleporter']);
    }
}
