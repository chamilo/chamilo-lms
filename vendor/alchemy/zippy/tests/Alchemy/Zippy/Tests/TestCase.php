<?php

namespace Alchemy\Zippy\Tests;

use Alchemy\Zippy\Adapter\AdapterInterface;
use Alchemy\Zippy\Resource\ResourceCollection;
use Alchemy\Zippy\Resource\Resource;
use Alchemy\Zippy\Adapter\VersionProbe\VersionProbeInterface;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public static function getResourcesPath()
    {
        $dir = __DIR__ . '/../../../resources';

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir;
    }

    protected function getResourceManagerMock($context = '', $elements = array())
    {
        $elements = array_map(function ($item) {
            return new Resource($item, $item);
        }, $elements);

        $collection = new ResourceCollection($context, $elements, false);

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

    protected function setProbeIsOk(AdapterInterface $adapter)
    {
        if (!method_exists($adapter, 'setVersionProbe')) {
            $this->fail('Trying to set a probe on an adapter that does not support it');
        }

        $probe = $this->getMock('Alchemy\Zippy\Adapter\VersionProbe\VersionProbeInterface');
        $probe->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(VersionProbeInterface::PROBE_OK));

        $adapter->setVersionProbe($probe);
    }

    protected function setProbeIsNotOk(AdapterInterface $adapter)
    {
        if (!method_exists($adapter, 'setVersionProbe')) {
            $this->fail('Trying to set a probe on an adapter that does not support it');
        }

        $probe = $this->getMock('Alchemy\Zippy\Adapter\VersionProbe\VersionProbeInterface');
        $probe->expects($this->any())
            ->method('getStatus')
            ->will($this->returnValue(VersionProbeInterface::PROBE_NOTSUPPORTED));

        $adapter->setVersionProbe($probe);
    }

    protected function getMockedProcessBuilderFactory($mockedProcessBuilder, $creations = 1)
    {
        $mockedProcessBuilderFactory = $this->getMock('Alchemy\Zippy\ProcessBuilder\ProcessBuilderFactoryInterface');

        $mockedProcessBuilderFactory
            ->expects($this->exactly($creations))
            ->method('create')
            ->will($this->returnValue($mockedProcessBuilder));

        return $mockedProcessBuilderFactory;
    }

    protected function getSuccessFullMockProcess($runs = 1)
    {
        $mockProcess = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $mockProcess
            ->expects($this->exactly($runs))
            ->method('run');

        $mockProcess
            ->expects($this->exactly($runs))
            ->method('isSuccessful')
            ->will($this->returnValue(true));

        return $mockProcess;
    }

    protected function getExpectedAbsolutePathForTarget($target)
    {
        $directory = dirname($target);

        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf('Unable to get the absolute path for %s', $target));
        }

        return realpath($directory).'/'.basename($target);
    }
}
