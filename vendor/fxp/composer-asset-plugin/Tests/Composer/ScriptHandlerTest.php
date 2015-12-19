<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Installer\PackageEvent;
use Composer\Repository\CompositeRepository;
use Fxp\Composer\AssetPlugin\Composer\ScriptHandler;

/**
 * Tests for the composer script handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var OperationInterface|InstallOperation|UpdateOperation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $operation;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    public function setUp()
    {
        $this->composer = $this->getMock('Composer\Composer');
        $this->io = $this->getMock('Composer\IO\IOInterface');
        $this->package = $this->getMock('Composer\Package\PackageInterface');

        $config = $this->getMock('Composer\Config');
        $config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) {
                $val = null;

                switch ($key) {
                    case 'cache-repo-dir':
                        return sys_get_temp_dir().'/composer-test-repo-cache';
                    case 'vendor-dir':
                        return sys_get_temp_dir().'/composer-test/vendor';
                }

                return $val;
            }));

        $rootPackage = $this->getMock('Composer\Package\RootPackageInterface');

        $this->composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));
        $this->composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($rootPackage));
    }

    public function tearDown()
    {
        $this->composer = null;
        $this->io = null;
        $this->operation = null;
        $this->package = null;
    }

    public function getPackageComposerTypes()
    {
        return array(
            array('npm-asset-library'),
            array('bower-asset-library'),
            array('library'),
        );
    }

    /**
     * @dataProvider getPackageComposerTypes
     */
    public function testDeleteIgnoreFiles($composerType)
    {
        $this->operation = $this->getMock('Composer\DependencyResolver\Operation\OperationInterface');

        ScriptHandler::deleteIgnoredFiles($this->createEvent($composerType));
    }

    /**
     * @dataProvider getPackageComposerTypes
     */
    public function testDeleteIgnoreFilesWithInstallOperation($composerType)
    {
        $this->operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\InstallOperation')
            ->disableOriginalConstructor()
            ->getMock();

        ScriptHandler::deleteIgnoredFiles($this->createEvent($composerType));
    }

    /**
     * @dataProvider getPackageComposerTypes
     */
    public function testDeleteIgnoreFilesWithUpdateOperation($composerType)
    {
        $this->operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\UpdateOperation')
            ->disableOriginalConstructor()
            ->getMock();

        ScriptHandler::deleteIgnoredFiles($this->createEvent($composerType));
    }

    /**
     * @param string $composerType
     *
     * @return PackageEvent
     */
    protected function createEvent($composerType)
    {
        $this->package->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($composerType));

        $this->operation->expects($this->any())
            ->method('getTargetPackage')
            ->will($this->returnValue($this->package));

        $this->operation->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));

        /* @var PolicyInterface $policy */
        $policy = $this->getMock('Composer\DependencyResolver\PolicyInterface');
        /* @var Pool $pool */
        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')->disableOriginalConstructor()->getMock();
        /* @var CompositeRepository $installedRepo */
        $installedRepo = $this->getMockBuilder('Composer\Repository\CompositeRepository')->disableOriginalConstructor()->getMock();
        /* @var Request $request */
        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')->disableOriginalConstructor()->getMock();
        $operations = array($this->getMock('Composer\DependencyResolver\Operation\OperationInterface'));

        return new PackageEvent('foo-event', $this->composer, $this->io, true, $policy, $pool, $installedRepo, $request, $operations, $this->operation);
    }
}
