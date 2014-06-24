<?php

namespace Alchemy\Zippy\Tests;

use Alchemy\Zippy\Zippy;
use Alchemy\Zippy\Exception\NoAdapterOnPlatformException;
use Alchemy\Zippy\Exception\FormatNotSupportedException;
use Alchemy\Zippy\Exception\RuntimeException;

class ZippyTest extends TestCase
{
    /** @test */
    public function itShouldCreateAnArchive()
    {
        $filename = 'file.zippo';
        $fileToAdd = 'file1';
        $recursive = true;

        $adapter = $this->getSupportedAdapter();

        $adapter->expects($this->once())
            ->method('create')
            ->with($this->equalTo($filename), $this->equalTo($fileToAdd), $this->equalTo($recursive));

        $adapters = array($adapter);
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        $zippy->create($filename, $fileToAdd, $recursive);
    }

    /** @test */
    public function itShouldCreateAnArchiveByForcingType()
    {
        $filename = 'file';
        $fileToAdd = 'file1';
        $recursive = true;

        $adapter = $this->getSupportedAdapter();

        $adapter->expects($this->once())
            ->method('create')
            ->with($this->equalTo($filename), $this->equalTo($fileToAdd), $this->equalTo($recursive));

        $adapters = array($adapter);
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        $zippy->create($filename, $fileToAdd, $recursive, 'zippo');
    }

    /** @test */
    public function itShouldNotCreateAndThrowAnException()
    {
        $filename = 'file';
        $fileToAdd = 'file1';
        $recursive = true;

        $adapter = $this->getSupportedAdapter();

        $adapter->expects($this->never())->method('create');

        $adapters = array($adapter);
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        try {
            $zippy->create($filename, $fileToAdd, $recursive, 'zippotte');
            $this->fail('Should have raised an exception');
        } catch (RuntimeException $e) {

        }
    }

    /** @test */
    public function itShouldOpenAnArchive()
    {
        $filename = 'file.zippo';

        $adapter = $this->getSupportedAdapter();

        $adapter->expects($this->once())
            ->method('open')
            ->with($this->equalTo($filename));

        $adapters = array($adapter);
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        $zippy->open($filename);
    }

    /** @test */
    public function itShouldExposeContainerPassedOnConstructor()
    {
        $container = $this->getContainer();

        $zippy = new Zippy($container);

        $this->assertEquals($container, $zippy->adapters);
    }

    /** @test */
    public function itShouldRegisterStrategies()
    {
        $adapters = array($this->getSupportedAdapter());
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        $this->assertEquals(array('zippo' => array($strategy)), $zippy->getStrategies());
    }

    /** @test */
    public function registerTwoStrategiesWithSameExtensionShouldBeinRightOrder()
    {
        $adapters1 = array($this->getSupportedAdapter());
        $strategy1 = $this->getStrategy('zippo', $adapters1);

        $adapters2 = array($this->getSupportedAdapter());
        $strategy2 = $this->getStrategy('zippo', $adapters2);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy1);
        $zippy->addStrategy($strategy2);

        $this->assertEquals(array('zippo' => array($strategy2, $strategy1)), $zippy->getStrategies());
    }

    /** @test */
    public function registerAStrategyTwiceShouldMoveItToLastAdded()
    {
        $adapters1 = array($this->getSupportedAdapter());
        $strategy1 = $this->getStrategy('zippo', $adapters1);

        $adapters2 = array($this->getSupportedAdapter());
        $strategy2 = $this->getStrategy('zippo', $adapters2);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy1);
        $zippy->addStrategy($strategy2);
        $zippy->addStrategy($strategy1);

        $this->assertEquals(array('zippo' => array($strategy1, $strategy2)), $zippy->getStrategies());
    }

    /** @test */
    public function itShouldReturnAnAdapterCorrespondingToTheRightStrategy()
    {
        $adapters = array($this->getSupportedAdapter());
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        $this->assertEquals($adapters[0], $zippy->getAdapterFor('zippo'));
        $this->assertEquals($adapters[0], $zippy->getAdapterFor('.zippo'));
        $this->assertEquals($adapters[0], $zippy->getAdapterFor('ziPPo'));
        $this->assertEquals($adapters[0], $zippy->getAdapterFor('.ZIPPO'));
    }

    /** @test */
    public function itShouldThrowAnExceptionIfNoAdapterSupported()
    {
        $adapters = array($this->getNotSupportedAdapter());
        $strategy = $this->getStrategy('zippo', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        try {
            $zippy->getAdapterFor('zippo');
            $this->fail('Should have raised an exception');
        } catch (NoAdapterOnPlatformException $e) {

        }
    }

    /** @test */
    public function itShouldThrowAnExceptionIfFormatNotSupported()
    {
        $adapters = array($this->getSupportedAdapter());
        $strategy = $this->getStrategy('zippotte', $adapters);

        $zippy = new Zippy($this->getContainer());
        $zippy->addStrategy($strategy);

        try {
            $zippy->getAdapterFor('zippo');
            $this->fail('Should have raised an exception');
        } catch (FormatNotSupportedException $e) {

        }
    }

    /** @test */
    public function loadShouldRegisterStrategies()
    {
        $zippy = Zippy::load();

        $this->assertCount(7, $zippy->getStrategies());

        $this->assertArrayHasKey('zip', $zippy->getStrategies());
        $this->assertArrayHasKey('tar', $zippy->getStrategies());
        $this->assertArrayHasKey('tar.gz', $zippy->getStrategies());
        $this->assertArrayHasKey('tar.bz2', $zippy->getStrategies());
        $this->assertArrayHasKey('tbz2', $zippy->getStrategies());
        $this->assertArrayHasKey('tb2', $zippy->getStrategies());
        $this->assertArrayHasKey('tgz', $zippy->getStrategies());
    }

    private function getStrategy($extension, $adapters)
    {
        $strategy = $this->getMock('Alchemy\Zippy\FileStrategy\FileStrategyInterface');

        $strategy->expects($this->any())
            ->method('getFileExtension')
            ->will($this->returnValue($extension));

        $strategy->expects($this->any())
            ->method('getAdapters')
            ->will($this->returnValue($adapters));

        return $strategy;
    }

    private function getSupportedAdapter()
    {
        $adapter = $this->getMock('Alchemy\Zippy\Adapter\AdapterInterface');
        $adapter->expects($this->any())
            ->method('isSupported')
            ->will($this->returnValue(true));

        return $adapter;
    }

    private function getNotSupportedAdapter()
    {
        $adapter = $this->getMock('Alchemy\Zippy\Adapter\AdapterInterface');
        $adapter->expects($this->any())
            ->method('isSupported')
            ->will($this->returnValue(false));

        return $adapter;
    }

    private function getContainer()
    {
        return $this->getMock('Alchemy\Zippy\Adapter\AdapterContainer');
    }
}
