<?php

namespace Alchemy\Zippy\Tests\Adapter;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Adapter\Resource\FileResource;

class FileResourceTest extends TestCase
{
    public function testGetResource()
    {
        $path = '/path/to/resource';
        $resource = new FileResource($path);

        $this->asserTEquals($path, $resource->getResource());
    }
}
