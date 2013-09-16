<?php

namespace Flint\Tests\Config;

use Flint\Config\ResourceCollection;

class ResourceCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectResources()
    {
        $resource = $this->getMock('Symfony\Component\Config\Resource\ResourceInterface');
        $resource1 = $this->getMock('Symfony\Component\Config\Resource\ResourceInterface');

        $collection = new ResourceCollection(array($resource));
        $collection->add($resource1);

        $this->assertSame(array($resource, $resource1), $collection->all());
    }
}
