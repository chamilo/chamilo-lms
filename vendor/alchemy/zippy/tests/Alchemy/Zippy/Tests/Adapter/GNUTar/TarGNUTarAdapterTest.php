<?php

namespace Alchemy\Zippy\Tests\Adapter\GNUTar;

use Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter;
use Alchemy\Zippy\Tests\Adapter\AdapterTestCase;
use Alchemy\Zippy\Parser\ParserFactory;

class TarGNUTarAdapterTest extends AdapterTestCase
{
    protected static $tarFile;

    /**
     * @var TarGNUTarAdapter
     */
    protected $adapter;

    public static function setUpBeforeClass()
    {
        self::$tarFile = sprintf('%s/%s.tar', self::getResourcesPath(), TarGNUTarAdapter::getName());

        if (file_exists(self::$tarFile)) {
            unlink(self::$tarFile);
        }
    }

    public static function tearDownAfterClass()
    {
        if (file_exists(self::$tarFile)) {
            unlink(self::$tarFile);
        }
    }

    public function setUp()
    {
        $this->adapter = $this->provideSupportedAdapter();
    }

    private function provideAdapter()
    {
        $inflator = $this->getMockBuilder('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactory')
                ->disableOriginalConstructor()
                ->setMethods(array('useBinary'))
                ->getMock();

        $outputParser = ParserFactory::create(TarGNUTarAdapter::getName());

        $manager = $this->getResourceManagerMock(__DIR__);

        return new TarGNUTarAdapter($outputParser, $manager, $inflator, $inflator);
    }

    protected function provideSupportedAdapter()
    {
        $adapter = $this->provideAdapter();
        $this->setProbeIsOk($adapter);

        return $adapter;
    }

    protected function provideNotSupportedAdapter()
    {
        $adapter = $this->provideAdapter();
        $this->setProbeIsNotOk($adapter);

        return $adapter;
    }

    public function testCreateNoFiles()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--create'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('-'))
            ->will($this->returnSelf());

        $nullFile = defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null';

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo(sprintf('--files-from %s', $nullFile)))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo(sprintf('> %s', $this->getExpectedAbsolutePathForTarget(self::$tarFile))))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->create(self::$tarFile, array());
    }

    public function testCreate()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--create'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo(sprintf('--file=%s', $this->getExpectedAbsolutePathForTarget(self::$tarFile))))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('setWorkingDirectory')
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo('lalalalala'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $manager = $this->getResourceManagerMock(__DIR__, array('lalalalala'));
        $outputParser = ParserFactory::create(TarGNUTarAdapter::getName());
        $this->adapter = new TarGNUTarAdapter($outputParser, $manager, $this->getMockedProcessBuilderFactory($mockedProcessBuilder), $this->getMockedProcessBuilderFactory($mockedProcessBuilder, 0));
        $this->setProbeIsOk($this->adapter);

        $this->adapter->create(self::$tarFile, array(__FILE__));
    }

    public function testOpen()
    {
        $archive = $this->adapter->open($this->getResource(self::$tarFile));
        $this->assertInstanceOf('Alchemy\Zippy\Archive\ArchiveInterface', $archive);

        return $archive;
    }

    public function testListMembers()
    {
        $resource = $this->getResource(self::$tarFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--utc'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('--list'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo('-v'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo(sprintf('--file=%s', $resource->getResource())))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->listMembers($resource);
    }

    public function testAddFile()
    {
        $resource = $this->getResource(self::$tarFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--append'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo(sprintf('--file=%s', $resource->getResource())))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->add($resource, array(__DIR__ . '/../TestCase.php'));
    }

    public function testgetVersion()
    {
        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--version'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->getInflatorVersion();
    }

    public function testExtract()
    {
        $resource = $this->getResource(self::$tarFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--extract'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo(sprintf('--file=%s', $resource->getResource())))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo('--overwrite-dir'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo('--overwrite'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $dir = $this->adapter->extract($resource);
        $pathinfo = pathinfo(self::$tarFile);
        $this->assertEquals($pathinfo['dirname'], $dir->getPath());
    }

    public function testExtractWithExtractDirPrecised()
    {
        $resource = $this->getResource(self::$tarFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--extract'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('--file=' . $resource->getResource()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo('--overwrite-dir'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo('--overwrite'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(4))
            ->method('add')
            ->with($this->equalTo('--directory'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(5))
            ->method('add')
            ->with($this->equalTo(__DIR__))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(6))
            ->method('add')
            ->with($this->equalTo(__FILE__))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $this->adapter->setInflator($this->getMockedProcessBuilderFactory($mockedProcessBuilder));

        $this->adapter->extractMembers($resource, array(__FILE__), __DIR__);
    }

    public function testRemoveMembers()
    {
        $resource = $this->getResource(self::$tarFile);

        $mockedProcessBuilder = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockedProcessBuilder
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('--delete'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('--file=' . $resource->getResource()))
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

    public function testGetName()
    {
        $this->assertEquals('gnu-tar', TarGNUTarAdapter::getName());
    }

    public function testGetDefaultInflatorBinaryName()
    {
        $this->assertEquals(array('gnutar', 'tar'), TarGNUTarAdapter::getDefaultInflatorBinaryName());
    }

    public function testGetDefaultDeflatorBinaryName()
    {
        $this->assertEquals(array('gnutar', 'tar'), TarGNUTarAdapter::getDefaultDeflatorBinaryName());
    }
}
