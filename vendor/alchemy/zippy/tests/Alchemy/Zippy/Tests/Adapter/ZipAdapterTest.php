<?php

namespace Alchemy\Zippy\Tests\Adapter;

use Alchemy\Zippy\Adapter\ZipAdapter;
use Alchemy\Zippy\Parser\ParserFactory;

class ZipAdapterTest extends AdapterTestCase
{
    protected static $zipFile;

    /**
     * @var ZipAdapter
     */
    protected $adapter;

    public static function setUpBeforeClass()
    {
        self::$zipFile = sprintf('%s/%s.zip', self::getResourcesPath(), ZipAdapter::getName());

        if (file_exists(self::$zipFile)) {
            unlink(self::$zipFile);
        }
    }

    public static function tearDownAfterClass()
    {
        if (file_exists(self::$zipFile)) {
            unlink(self::$zipFile);
        }
    }

    public function setUp()
    {
        $this->adapter = $this->provideSupportedAdapter();
    }

    protected function provideNotSupportedAdapter()
    {
        $inflator = $deflator = $this->getMockBuilder('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactory')
                                    ->disableOriginalConstructor()
                                    ->setMethods(array('useBinary'))
                                    ->getMock();

        $outputParser = ParserFactory::create(ZipAdapter::getName());

        $adapter = new ZipAdapter($outputParser, $this->getResourceManagerMock(), $inflator, $deflator);
        $this->setProbeIsNotOk($adapter);

        return $adapter;
    }

    protected function provideSupportedAdapter()
    {
        $inflator = $deflator = $this->getMockBuilder('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactory')
                                    ->disableOriginalConstructor()
                                    ->setMethods(array('useBinary'))
                                    ->getMock();

        $outputParser = ParserFactory::create(ZipAdapter::getName());

        $adapter = new ZipAdapter($outputParser, $this->getResourceManagerMock(), $inflator, $deflator);
        $this->setProbeIsOk($adapter);

        return $adapter;
    }

    /**
     * @expectedException Alchemy\Zippy\Exception\NotSupportedException
     */
    public function testCreateNoFiles()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->create(self::$zipFile, array());
    }

    public function testCreate()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-r'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo($this->getExpectedAbsolutePathForTarget(self::$zipFile)))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('setWorkingDirectory')
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo('lalala'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $manager = $this->getResourceManagerMock(__DIR__, array('lalala'));
        $outputParser = ParserFactory::create(ZipAdapter::getName());
        $deflator = $this->getMockBuilder('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactory')
                                    ->disableOriginalConstructor()
                                    ->setMethods(array('useBinary'))
                                    ->getMock();

        $this->adapter = new ZipAdapter($outputParser, $manager, $this->getMockedProcessBuilderFactory($mockedProcessBuilder), $deflator);
        $this->setProbeIsOk($this->adapter);

        $this->adapter->create(self::$zipFile, array(__FILE__));

        return self::$zipFile;
    }

    /**
     * @depends testCreate
     */
    public function testOpen($zipFile)
    {
        $archive = $this->adapter->open($this->getResource($zipFile));
        $this->assertInstanceOf('Alchemy\Zippy\Archive\ArchiveInterface', $archive);
    }

    public function testListMembers()
    {
        $resource = $this->getResource(self::$zipFile);
        $archive = $this->adapter->open($resource);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-l'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo($resource->getResource()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setDeflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->listMembers($resource);
    }

    public function testAddFile()
    {
        $resource = $this->getResource(self::$zipFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-r'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('-u'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo($resource->getResource()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->add($resource, array(__DIR__ . '/../TestCase.php'));
    }

    public function testgetInflatorVersion()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-h'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setParser($this->getMock('Alchemy\Zippy\Parser\ParserInterface'));
        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->getInflatorVersion();
    }

    public function testgetDeflatorVersion()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-h'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setParser($this->getMock('Alchemy\Zippy\Parser\ParserInterface'));
        $this->adapter->setDeflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->getDeflatorVersion();
    }

    public function testRemoveMembers()
    {
        $resource = $this->getResource(self::$zipFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-d'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo($resource->getResource()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo(__DIR__ . '/../TestCase.php'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo('path-to-file'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $archiveFileMock = $this->getMock('Alchemy\Zippy\Archive\MemberInterface');

        $archiveFileMock
            ->expects($this->any())
            ->method('getLocation')
            ->will($this->returnValue('path-to-file'));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->remove($resource, array(
            __DIR__ . '/../TestCase.php',
            $archiveFileMock
        ));
    }

    public function testExtract()
    {
        $resource = $this->getResource(self::$zipFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('-o'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo($resource->getResource()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setDeflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $dir = $this->adapter->extract($resource);
        $pathinfo = pathinfo(self::$zipFile);
        $this->assertEquals($pathinfo['dirname'], $dir->getPath());
    }

    public function testExtractWithExtractDirPrecised()
    {
        $resource = $this->getResource(self::$zipFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo($resource->getResource()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('-d'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo(__DIR__))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo(__FILE__))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setDeflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->extractMembers($resource, array(__FILE__), __DIR__);
    }

    public function testGetName()
    {
        $this->assertEquals('zip', ZipAdapter::getName());
    }

    public function testGetDefaultInflatorBinaryName()
    {
        $this->assertEquals(array('zip'), ZipAdapter::getDefaultInflatorBinaryName());
    }

    public function testGetDefaultDeflatorBinaryName()
    {
        $this->assertEquals(array('unzip'), ZipAdapter::getDefaultDeflatorBinaryName());
    }
}
