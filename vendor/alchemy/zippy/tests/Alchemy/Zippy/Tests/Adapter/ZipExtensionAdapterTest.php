<?php

namespace Alchemy\Zippy\Tests\Adapter;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Adapter\ZipExtensionAdapter;
use Alchemy\Zippy\Adapter\Resource\ZipArchiveResource;

class ZipExtensionAdapterTest extends TestCase
{
    private $adapter;

    public function setUp()
    {
        $this->adapter = new ZipExtensionAdapter($this->getResourceManagerMock());
    }

    public function testNewInstance()
    {
        $adapter = ZipExtensionAdapter::newInstance();

        $this->assertInstanceOf('Alchemy\Zippy\Adapter\ZipExtensionAdapter', $adapter);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\NotSupportedException
     */
    public function testCreateNoFiles()
    {
        $this->adapter->create(__DIR__ . '/zip-file.zip', array());
    }

    public function testCreate()
    {
        $file = __DIR__ . '/zip-file.zip';
        $manager = $this->getResourceManagerMock(__DIR__, array(__FILE__));
        $this->adapter = new ZipExtensionAdapter($manager);
        $archive = $this->adapter->create($file, array(__FILE__));
        $this->assertInstanceOf('Alchemy\Zippy\Archive\Archive', $archive);
        $this->assertFileExists($file);
        unlink($file);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\RuntimeException
     */
    public function testOpenWithWrongFileName()
    {
        $file = __DIR__ . '/zip-file.zip';
        $this->adapter->open($file);
    }

    public function testOpen()
    {
        $file = __DIR__ . '/zip-file.zip';
        touch($file);
        $archive = $this->adapter->open($file);
        $this->assertInstanceOf('Alchemy\Zippy\Archive\Archive', $archive);
        unlink($file);
    }

    public function testIsSupported()
    {
        $this->assertInternalType('boolean', $this->adapter->isSupported());
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->adapter->getName());
    }

    public function testListMembers()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $members = $this->adapter->listMembers(new ZipArchiveResource($resource));

        $this->assertInternalType('array', $members);
    }

    public function testExtract()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->once())
            ->method('extractTo')
            ->with($this->equalTo(__DIR__), $this->anything())
            ->will($this->returnValue(true));

        $this->adapter->extract(new ZipArchiveResource($resource), __DIR__);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\InvalidArgumentException
     */
    public function testExtractOnError()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->once())
            ->method('extractTo')
            ->with($this->equalTo(__DIR__), $this->anything())
            ->will($this->returnValue(false));

        $this->adapter->extract(new ZipArchiveResource($resource), __DIR__);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\InvalidArgumentException
     */
    public function testExtractWithInvalidTarget()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter->extract(new ZipArchiveResource($resource), __DIR__ . '/boursin');
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\InvalidArgumentException
     */
    public function testExtractWithInvalidTarget2()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter->extract(new ZipArchiveResource($resource));
    }

    public function testRemove()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $files = array(
            'one-file.jpg',
            'second-file.jpg',
        );

        $resource->expects($this->exactly(2))
            ->method('locateName')
            ->will($this->returnValue(true));

        $resource->expects($this->exactly(2))
            ->method('deleteName')
            ->will($this->returnValue(true));

        $this->adapter->remove(new ZipArchiveResource($resource), $files);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\InvalidArgumentException
     */
    public function testRemoveWithLocateFailing()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $files = array(
            'one-file.jpg'
        );

        $resource->expects($this->once())
            ->method('locateName')
            ->with($this->equalTo('one-file.jpg'))
            ->will($this->returnValue(false));

        $this->adapter->remove(new ZipArchiveResource($resource), $files);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\RuntimeException
     */
    public function testRemoveWithDeleteFailing()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $files = array(
            'one-file.jpg'
        );

        $resource->expects($this->once())
            ->method('locateName')
            ->with($this->equalTo('one-file.jpg'))
            ->will($this->returnValue(true));

        $resource->expects($this->once())
            ->method('deleteName')
            ->with($this->equalTo('one-file.jpg'))
            ->will($this->returnValue(false));

        $this->adapter->remove(new ZipArchiveResource($resource), $files);
    }

    public function testAdd()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->once())
            ->method('addFile')
            ->will($this->returnValue(true));

        $resource->expects($this->once())
            ->method('addEmptyDir')
            ->will($this->returnValue(true));

        $dir = __DIR__ . '/temp-dir';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $files = array(
            __FILE__,
            $dir,
        );

        $manager = $this->getResourceManagerMock(__DIR__, $files);
        $this->adapter = new ZipExtensionAdapter($manager);
        $this->adapter->add(new ZipArchiveResource($resource), $files);

        rmdir($dir);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\RuntimeException
     */
    public function testAddFailOnFile()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->once())
            ->method('addFile')
            ->will($this->returnValue(false));

        $dir = __DIR__ . '/temp-dir';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $files = array(
            __FILE__,
            $dir,
        );

        $manager = $this->getResourceManagerMock(__DIR__, $files);
        $this->adapter = new ZipExtensionAdapter($manager);
        $this->adapter->add(new ZipArchiveResource($resource), $files);
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\RuntimeException
     */
    public function testAddFailOnDir()
    {
        $resource = $this->getMockBuilder('\ZipArchive')
            ->disableOriginalConstructor()
            ->getMock();

        $resource->expects($this->once())
            ->method('addFile')
            ->will($this->returnValue(true));

        $resource->expects($this->once())
            ->method('addEmptyDir')
            ->will($this->returnValue(false));

        $dir = __DIR__ . '/temp-dir';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $files = array(
            __FILE__,
            $dir,
        );

        $manager = $this->getResourceManagerMock(__DIR__, $files);
        $this->adapter = new ZipExtensionAdapter($manager);
        $this->adapter->add(new ZipArchiveResource($resource), $files);
    }
}
