<?php

namespace Alchemy\Zippy\Tests;

use Alchemy\Zippy\Resource\ResourceCollection;
use Alchemy\Zippy\Resource\Resource;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public static function getResourcesPath()
    {
        return __DIR__ . '/../../../resources';
    }

    protected function getResourceManagerMock($context = '', $elements = array())
    {
        $elements = array_map(function ($item) {
            return new Resource($item, $item);
        }, $elements);

        $collection = new ResourceCollection($context, $elements);

        $manager = $this
            ->getMockBuilder('Alchemy\Zippy\Resource\ResourceManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('handle')
            ->will($this->returnValue($collection));

        return $manager;
    }

    protected function getResource($data = null)
    {
        $resource = $this->getMock('Alchemy\Zippy\Adapter\Resource\ResourceInterface');

        if (null !== $data) {
            $resource->expects($this->any())
                ->method('getResource')
                ->will($this->returnValue($data));
        }

        return $resource;
    }
}
