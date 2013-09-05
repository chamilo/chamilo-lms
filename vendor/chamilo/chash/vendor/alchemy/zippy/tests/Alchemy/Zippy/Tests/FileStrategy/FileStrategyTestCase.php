<?php

namespace Alchemy\Zippy\Tests\FileStrategy;

use Alchemy\Zippy\Adapter\AdapterInterface;
use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\FileStrategy\FileStrategyInterface;

abstract class FileStrategyTestCase extends TestCase
{
    /** @test */
    public function getFileExtensionShouldReturnAnString()
    {
        $that = $this;
        $container = $this->getMock('Alchemy\Zippy\Adapter\AdapterContainer');
        $container
                ->expects($this->any())
                ->method('offsetGet')
                ->will($this->returnCallback(function($offset) use ($that) {
                    if (array_key_exists('Alchemy\Zippy\Adapter\AdapterInterface', class_implements($offset))) {
                        return $that->getMock('Alchemy\Zippy\Adapter\AdapterInterface');
                    }

                    return null;
                }));

        $extension = $this->getStrategy($container)->getFileExtension();

        $this->assertNotEquals('', trim($extension));
        $this->assertInternalType('string', $extension);
    }

    /** @test */
    public function getAdaptersShouldReturnAnArrayOfAdapter()
    {
        $that = $this;
        $container = $this->getMock('Alchemy\Zippy\Adapter\AdapterContainer');
        $container
                ->expects($this->any())
                ->method('offsetGet')
                ->will($this->returnCallback(function($offset) use ($that) {
                    if (array_key_exists('Alchemy\Zippy\Adapter\AdapterInterface', class_implements($offset))) {
                        return $that->getMock('Alchemy\Zippy\Adapter\AdapterInterface');
                    }

                    return null;
                }));

        $adapters = $this->getStrategy($container)->getAdapters();

        $this->assertInternalType('array', $adapters);

        foreach ($adapters as $adapter) {
            $this->assertInstanceOf('Alchemy\\Zippy\\Adapter\\AdapterInterface', $adapter);
        }
    }

    /**
     * @return FileStrategyInterface
     */
    abstract protected function getStrategy($container);
}
