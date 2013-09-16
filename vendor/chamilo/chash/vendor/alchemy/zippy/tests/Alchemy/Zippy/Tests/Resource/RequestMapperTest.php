<?php

namespace Alchemy\Zippy\Tests\Resource;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Resource\RequestMapper;

class RequestMapperTest extends TestCase
{
    /**
     * @covers Alchemy\Zippy\Resource\RequestMapper::map
     */
    public function testMap()
    {
        $locator = $this->getMockBuilder('Alchemy\Zippy\Resource\TargetLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $locator->expects($this->any())
            ->method('locate')
            ->will($this->returnValue('computed-location'));

        $mapper = new RequestMapper($locator);

        $collection = $mapper->map(__DIR__, array(
            __DIR__ . '/input/path/to/local/file.ext',
            __DIR__ . '/input/path/to/local/file2.ext',
            'here' => __DIR__ . '/input/path/to/local/file3.ext',
        ));

        $this->assertInstanceOf('Alchemy\Zippy\Resource\ResourceCollection', $collection);
        $this->assertCount(3, $collection);

        $firstFound = $secondFound = $thirdFound = false;
        foreach ($collection as $resource) {
            $this->assertInstanceOf('Alchemy\Zippy\Resource\Resource', $resource);

            if (__DIR__ . '/input/path/to/local/file.ext' === $resource->getOriginal()) {
                $firstFound = true;
                $this->assertEquals('computed-location', $resource->getTarget());
            } elseif (__DIR__ . '/input/path/to/local/file2.ext' === $resource->getOriginal()) {
                $secondFound = true;
                $this->assertEquals('computed-location', $resource->getTarget());
            } elseif (__DIR__ . '/input/path/to/local/file3.ext' === $resource->getOriginal()) {
                $thirdFound = true;
                $this->assertEquals('here', $resource->getTarget());
            } else {
                $this->fail('Unexpected content');
            }
        }

        if (!$firstFound || !$secondFound) {
            $this->fail('Unable to find all of the input in the output');
        }
    }

    /**
     * @covers Alchemy\Zippy\Resource\RequestMapper::create
     */
    public function testCreate()
    {
        $mapper = RequestMapper::create();
        $this->assertInstanceOf('Alchemy\Zippy\Resource\RequestMapper', $mapper);
    }
}
