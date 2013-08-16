<?php

namespace Alchemy\Zippy\Tests\Resource;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Resource\ResourceManager;

class ResourceManagerTest extends TestCase
{
    /**
     * @covers Alchemy\Zippy\Resource\ResourceManager::handle
     */
    public function testHandle()
    {
        $mapper = $this->getRequestMapperMock();

        $manager = new ResourceManager(
            $mapper,
            $this->getResourceTeleporterMock(),
            $this->getFilesystemMock()
        );

        $context = '/path/to/current/directory';
        $request = array('/path/to/a/file1', '/path/to/a/file2');

        $expectedCollection = $this->getMockBuilder('Alchemy\Zippy\Resource\ResourceCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $expectedCollection->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator(array())));

        $mapper->expects($this->once())
            ->method('map')
            ->with($this->equalTo($context), $this->equalTo($request))
            ->will($this->returnValue($expectedCollection));

        $collection = $manager->handle($context, $request);
        $this->assertEquals($expectedCollection, $collection);
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceManager::cleanup
     */
    public function testCleanup()
    {
        $fs = $this->getFilesystemMock();

        $manager = new ResourceManager(
            $this->getRequestMapperMock(),
            $this->getResourceTeleporterMock(),
            $fs
        );

        $context = 'context' . mt_rand();

        $fs->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($context));

        $collection = $this->getMockBuilder('Alchemy\Zippy\Resource\ResourceCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $collection->expects($this->once())
            ->method('isTemporary')
            ->will($this->returnValue(true));

        $collection->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));

        $manager->cleanup($collection);
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceManager::cleanup
     */
    public function testCleanupWhenCollectionIsNotTemporary()
    {
        $fs = $this->getFilesystemMock();

        $manager = new ResourceManager(
            $this->getRequestMapperMock(),
            $this->getResourceTeleporterMock(),
            $fs
        );

        $fs->expects($this->never())
            ->method('remove');

        $collection = $this->getMockBuilder('Alchemy\Zippy\Resource\ResourceCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $collection->expects($this->once())
            ->method('isTemporary')
            ->will($this->returnValue(false));

        $collection->expects($this->never())
            ->method('getContext');

        $manager->cleanup($collection);
    }

    /**
     * @covers Alchemy\Zippy\Resource\ResourceManager::handle
     */
    public function testFunctionnal()
    {
        $wd = __DIR__;
        $tmpdir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->mkdir($tmpdir . '/path/to/local/');
        $filesystem->mkdir($tmpdir . '/to/');
        $filesystem->mkdir($tmpdir . '/path/to/a');

        touch($tmpdir . '/path/to/local/file.ext');
        touch($tmpdir . '/path/to/local/file2.ext');
        touch($tmpdir . '/to/file3.ext');

        $request = array(
            $wd . '/input/path/to/local/file.ext',
            $wd . '/input/path/to/a/../local/file2.ext',
            $tmpdir . '/path/to/local/file.ext',
            $tmpdir . '/path/to/a/../local/file2.ext',
            'http://www.google.com/+/business/images/plus-badge.png',
            'http://www.google.com/+/business/images/plusone-button.png',
            'file://' . $tmpdir . '/to/file3.ext',
            'file://' . $wd . '/input/path/to/a/../local/file3.ext',
            '/I/want/this/file/to/go/there' => 'file://' . $wd . '/input/path/to/local/file2.ext',
            '/I/want/this/file/to/go/here'  => 'file://' . $wd . '/input/path/to/local/file3.ext'
        );

        $expected = array(
            'input/path/to/local/file.ext',
            'input/path/to/local/file2.ext',
            'file.ext',
            'file2.ext',
            'plus-badge.png',
            'plusone-button.png',
            'file3.ext',
            'input/path/to/local/file3.ext',
            'I/want/this/file/to/go/there',
            'I/want/this/file/to/go/here',
        );
        $expectedSource = array(
            $request[0],
            $request[1],
            $request[2],
            $request[3],
            $request[4],
            $request[5],
            $request[6],
            $request[7],
            $request['/I/want/this/file/to/go/there'],
            $request['/I/want/this/file/to/go/here'],
        );

        $resourceManger = ResourceManager::create();

        $collection = $resourceManger->handle($wd, $request);

        $this->assertCount(10, $collection);

        $n = 0;
        foreach ($collection as $resource) {
            $this->assertEquals($expected[$n], $resource->getTarget());
            $this->assertEquals($expectedSource[$n], $resource->getOriginal());
            $n++;
        }
    }

    protected function getRequestMapperMock()
    {
        return $this->getMockBuilder('Alchemy\Zippy\Resource\RequestMapper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getResourceTeleporterMock()
    {
        return $this->getMockBuilder('Alchemy\Zippy\Resource\ResourceTeleporter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getFilesystemMock()
    {
        return $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
