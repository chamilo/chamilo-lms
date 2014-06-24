<?php

namespace Alchemy\Zippy\Tests\Adapter\BSDTar;

use Alchemy\Zippy\Tests\Adapter\AdapterTestCase;
use Alchemy\Zippy\Parser\ParserFactory;

abstract class BSDTarAdapterWithOptionsTest extends AdapterTestCase
{
    protected static $tarFile;

    /**
     * @var AbstractBSDTarAdapter
     */
    protected $adapter;

    public static function setUpBeforeClass()
    {
        $classname = static::getAdapterClassName();
        self::$tarFile = sprintf('%s/%s.tar', self::getResourcesPath(), $classname::getName());

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
        $classname = static::getAdapterClassName();

        $inflator = $this->getMockBuilder('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactory')
                ->disableOriginalConstructor()
                ->setMethods(array('useBinary'))
                ->getMock();

        $outputParser = ParserFactory::create($classname::getName());

        $manager = $this->getResourceManagerMock(__DIR__);

        return new $classname($outputParser, $manager, $inflator, $inflator);
    }

    protected function provideNotSupportedAdapter()
    {
        $adapter = $this->provideAdapter();
        $this->setProbeIsNotOk($adapter);

        return $adapter;
    }

    protected function provideSupportedAdapter()
    {
        $adapter = $this->provideAdapter();
        $this->setProbeIsOk($adapter);

        return $adapter;
    }

    public function testNewinstance()
    {
        $classname = static::getAdapterClassName();
        $finder = $this->getMockBuilder('Symfony\Component\Process\ExecutableFinder')
            ->disableOriginalConstructor()
            ->getMock();
        $manager = $this->getMockBuilder('Alchemy\Zippy\Resource\ResourceManager')
            ->disableOriginalConstructor()
            ->getMock();
        $instance = $classname::newInstance($finder, $manager, $this->getMock('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactoryInterface'), $this->getMock('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactoryInterface'));
        $this->assertInstanceOf($classname, $instance);
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
            ->with($this->equalTo($this->getOptions()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo('-'))
            ->will($this->returnSelf());

        $nullFile = defined('PHP_WINDOWS_VERSION_BUILD') ? 'NUL' : '/dev/null';

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo(sprintf('--files-from %s', $nullFile)))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(4))
            ->method('add')
            ->with($this->equalTo((sprintf('> %s', $this->getExpectedAbsolutePathForTarget(self::$tarFile)))))
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
            ->with($this->equalTo($this->getOptions()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo(sprintf('--file=%s', $this->getExpectedAbsolutePathForTarget(self::$tarFile))))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('setWorkingDirectory')
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(4))
            ->method('add')
            ->with($this->equalTo('lalalalala'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->once())
            ->method('getProcess')
            ->will($this->returnValue($this->getSuccessFullMockProcess()));

        $classname = static::getAdapterClassName();
        $outputParser = ParserFactory::create($classname::getName());
        $manager = $this->getResourceManagerMock(__DIR__, array('lalalalala'));

        $this->adapter = new $classname($outputParser, $manager, $this->getMockedProcessBuilderFactory($mockedProcessBuilder), $this->getMockedProcessBuilderFactory($mockedProcessBuilder, 0));
        $this->setProbeIsOk($this->adapter);

        $this->adapter->create(self::$tarFile, array(__FILE__));

        return self::$tarFile;
    }

    /**
     * @depends testCreate
     */
    public function testOpen($tarFile)
    {
        $archive = $this->adapter->open($tarFile);
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
            ->with($this->equalTo('--list'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('-v'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo(sprintf('--file=%s', $resource->getResource())))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo($this->getOptions()))
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
        $this->setExpectedException('Alchemy\Zippy\Exception\NotSupportedException', 'Updating a compressed tar archive is not supported.');
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
            ->with($this->equalTo($this->getOptions()))
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
            ->with($this->equalTo($this->getOptions()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo('--directory'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(4))
            ->method('add')
            ->with($this->equalTo(__DIR__))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(5))
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
            ->with($this->equalTo($this->getOptions()))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(3))
            ->method('add')
            ->with($this->equalTo(__DIR__ . '/../TestCase.php'))
            ->will($this->returnSelf());

        $mockedProcessBuilder
            ->expects($this->at(4))
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
        $classname = static::getAdapterClassName();
        $this->assertEquals('bsd-tar', $classname::getName());
    }

    public function testGetDefaultInflatorBinaryName()
    {
        $classname = static::getAdapterClassName();
        $this->assertEquals(array('bsdtar', 'tar'), $classname::getDefaultInflatorBinaryName());
    }

    public function testGetDefaultDeflatorBinaryName()
    {
        $classname = static::getAdapterClassName();
        $this->assertEquals(array('bsdtar', 'tar'), $classname::getDefaultDeflatorBinaryName());
    }

    abstract protected function getOptions();

    protected static function getAdapterClassName()
    {
        self::fail(sprintf('Method %s should be implemented', __METHOD__));
    }
}
