<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository\Vcs;

use Composer\Test\Repository\Vcs\PerforceDriverTest as BasePerforceDriverTest;
use Fxp\Composer\AssetPlugin\Repository\Vcs\PerforceDriver;
use Fxp\Composer\AssetPlugin\Util\Perforce;

/**
 * Tests of vcs perforce repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class PerforceDriverTest extends BasePerforceDriverTest
{
    /**
     * @var PerforceDriver
     */
    protected $driver;

    /**
     * @var Perforce|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $perforce;

    protected function setUp()
    {
        parent::setUp();

        $this->driver = new PerforceDriver($this->repoConfig, $this->io, $this->config, $this->process, $this->remoteFileSystem);
        $this->overrideDriverInternalPerforce($this->perforce);
    }

    public function testInitializeCapturesVariablesFromRepoConfig()
    {
        $driver = new PerforceDriver($this->repoConfig, $this->io, $this->config, $this->process, $this->remoteFileSystem);
        $driver->initialize();
        $this->assertEquals(self::TEST_URL, $driver->getUrl());
        $this->assertEquals(self::TEST_DEPOT, $driver->getDepot());
        $this->assertEquals(self::TEST_BRANCH, $driver->getBranch());
    }

    /**
     * Test that supports() simply return false.
     *
     * @covers \Composer\Repository\Vcs\PerforceDriver::supports
     */
    public function testSupportsReturnsFalseNoDeepCheck()
    {
        $this->expectOutputString('');
        $this->assertFalse(PerforceDriver::supports($this->io, $this->config, 'existing.url'));
    }

    public function testInitializeLogsInAndConnectsClient()
    {
        $this->perforce->expects($this->at(0))->method('p4Login');
        $this->perforce->expects($this->at(1))->method('checkStream');
        $this->perforce->expects($this->at(2))->method('writeP4ClientSpec');
        $this->perforce->expects($this->at(3))->method('connectClient');
        $this->driver->initialize();
    }

    public function testPublicRepositoryWithEmptyComposer()
    {
        $identifier = 'TEST_IDENTIFIER';
        $this->perforce->expects($this->any())
            ->method('getComposerInformation')
            ->with($this->equalTo($identifier))
            ->will($this->returnValue(''));

        $this->driver->initialize();
        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        $this->assertSame($validEmpty, $this->driver->getComposerInformation($identifier));
    }

    public function testPublicRepositoryWithCodeCache()
    {
        $identifier = 'TEST_IDENTIFIER';
        $this->perforce->expects($this->any())
            ->method('getComposerInformation')
            ->with($this->equalTo($identifier))
            ->will($this->returnValue(array('name' => 'foo')));

        $this->driver->initialize();
        $composer1 = $this->driver->getComposerInformation($identifier);
        $composer2 = $this->driver->getComposerInformation($identifier);

        $this->assertNotNull($composer1);
        $this->assertNotNull($composer2);
        $this->assertSame($composer1, $composer2);
    }

    public function testPublicRepositoryWithFilesystemCache()
    {
        $identifier = 'TEST_IDENTIFIER';
        $this->perforce->expects($this->any())
            ->method('getComposerInformation')
            ->with($this->equalTo($identifier))
            ->will($this->returnValue(array('name' => 'foo')));

        $driver2 = new PerforceDriver($this->repoConfig, $this->io, $this->config, $this->process, $this->remoteFileSystem);
        $reflectionClass = new \ReflectionClass($driver2);
        $property = $reflectionClass->getProperty('perforce');
        $property->setAccessible(true);
        $property->setValue($driver2, $this->perforce);

        $this->driver->initialize();
        $driver2->initialize();

        $composer1 = $this->driver->getComposerInformation($identifier);
        $composer2 = $driver2->getComposerInformation($identifier);

        $this->assertNotNull($composer1);
        $this->assertNotNull($composer2);
        $this->assertSame($composer1, $composer2);
    }

    protected function getTestRepoConfig()
    {
        return array_merge(parent::getTestRepoConfig(), array(
            'asset-type' => 'ASSET',
            'filename' => 'ASSET.json',
        ));
    }

    protected function getMockPerforce()
    {
        $methods = array('p4login', 'checkStream', 'writeP4ClientSpec', 'connectClient', 'getComposerInformation', 'cleanupClientSpec');

        return $this->getMockBuilder('Fxp\Composer\AssetPlugin\Util\Perforce', $methods)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
