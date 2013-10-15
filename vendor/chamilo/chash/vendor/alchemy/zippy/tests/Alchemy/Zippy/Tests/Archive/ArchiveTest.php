<?php

namespace Alchemy\Zippy\Tests\Archive;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Archive\ArchiveInterface;
use Alchemy\Zippy\Archive\Archive;

class ArchiveTest extends TestCase
{
    public function testNewInstance()
    {
        $archive = new Archive($this->getResource('location'), $this->getAdapterMock(), $this->getResourceManagerMock());

        $this->assertTrue($archive instanceof ArchiveInterface);

        return $archive;
    }

    public function testCount()
    {
        $mockAdapter = $this->getAdapterMock();

        $mockAdapter
            ->expects($this->once())
            ->method('listMembers')
            ->will($this->returnValue(array('1', '2')));

        $archive = new Archive($this->getResource('location'), $mockAdapter, $this->getResourceManagerMock());

        $this->assertEquals(2, count($archive));
    }

    public function testGetMembers()
    {
        $mockAdapter = $this->getAdapterMock();

        $resource = $this->getResource('location');

        $mockAdapter
            ->expects($this->once())
            ->method('listMembers')
            ->with($this->equalTo($resource))
            ->will($this->returnValue(array('1', '2')));

        $archive = new Archive($this->getResource('location'), $mockAdapter, $this->getResourceManagerMock());

        $members = $archive->getMembers();

        $this->assertTrue(is_array($members));
        $this->assertEquals(2, count($members));
    }

    public function testAddMembers()
    {
        $resource = $this->getResource('location');

        $mockAdapter = $this->getAdapterMock();

        $mockAdapter
            ->expects($this->once())
            ->method('add')
            ->with($this->equalTo($resource), $this->equalTo(array('hello')), $this->equalTo(true));

        $resourceManager = $this->getResourceManagerMock();

        $archive = new Archive($resource, $mockAdapter, $resourceManager);

        $this->assertEquals($archive, $archive->addMembers(array('hello')));
    }

    public function testRemoveMember()
    {
        $mockAdapter = $this->getAdapterMock();

        $mockAdapter
            ->expects($this->once())
            ->method('remove');

        $archive = new Archive($this->getResource('location'), $mockAdapter, $this->getResourceManagerMock());

        $this->assertEquals($archive, $archive->removeMembers('hello'));
    }

    private function getAdapterMock()
    {
        return $this->getMock('Alchemy\Zippy\Adapter\AdapterInterface');
    }
}
