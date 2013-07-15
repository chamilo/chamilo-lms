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
        $collection = new ResourceCollection('supa-context');
        $this->assertEquals('supa-context', $collection->getContext());
        $this->assertEquals(array(), $collection->toArray());
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceCollection::__construct
     */
    public function testConstructWithElements()
    {
        $data = array('one', 'two' => 'three');
        $collection = new ResourceCollection('supa-context', $data);
        $this->assertEquals('supa-context', $collection->getContext());
        $this->assertEquals($data, $collection->toArray());
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceCollection::getContext
     * @covers Alchemy\Zippy\Resource\ResourceCollection::setContext
     */
    public function testGetSetContext()
    {
        $collection = new ResourceCollection('supa-context');
        $this->assertEquals('supa-context', $collection->getContext());
        $collection->setContext('cool context');
        $this->assertEquals('cool context', $collection->getContext());
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceCollection::isTemporary
     * @covers Alchemy\Zippy\Resource\ResourceCollection::setTemporary
     */
    public function testSetIsTemporary()
    {
        $collection = new ResourceCollection('supa-context');
        $this->assertFalse($collection->isTemporary());
        $collection->setTemporary(true);
        $this->assertTrue($collection->isTemporary());
        $collection->setTemporary(false);
        $this->assertFalse($collection->isTemporary());
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
        ));

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
