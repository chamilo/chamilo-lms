<?php

namespace Alchemy\Zippy\Tests\Resource;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Resource\ResourceCollection;

class ResourceCollectionTest extends TestCase
{
    /**
     * @covers Alchemy\Zippy\Resource\ResourceCollection::__construct
     */
    public function testConstructWithoutElements()
    {
        $collection = new ResourceCollection('supa-context', array(), false);
        $this->assertEquals('supa-context', $collection->getContext());
        $this->assertEquals(array(), $collection->toArray());
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceCollection::__construct
     */
    public function testConstructWithElements()
    {
        $data = array($this->createResourceMock(), 'two' => $this->createResourceMock());
        $collection = new ResourceCollection('supa-context', $data, false);
        $this->assertEquals('supa-context', $collection->getContext());
        $this->assertEquals($data, $collection->toArray());
    }

    private function createResourceMock()
    {
        return $this->getMockBuilder('Alchemy\Zippy\Resource\Resource')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceCollection::canBeProcessedInPlace
     * @dataProvider provideVariousInPlaceResources
     */
    public function testCanBeProcessedInPlace($expected, $first, $second, $third)
    {
        $collection = new ResourceCollection('supa-context', array(
            $this->getInPlaceResource($first),
            $this->getInPlaceResource($second),
            $this->getInPlaceResource($third),
        ), false);

        $this->assertInternalType('boolean', $collection->canBeProcessedInPlace());
        $this->assertEquals($expected, $collection->canBeProcessedInPlace());
    }

    public function provideVariousInPlaceResources()
    {
        return array(
            array(true, true, true, true),
            array(false, true, true, false),
            array(false, false, false, false),
            array(false, false, false, true),
        );
    }

    private function getInPlaceResource($processInPlace)
    {
        $resource = $this->getMockBuilder('Alchemy\Zippy\Resource\Resource')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->any())
            ->method('canBeProcessedInPlace')
            ->will($this->returnValue($processInPlace));

        return $resource;
    }
}
