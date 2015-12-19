<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Package\Loader;

use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\Loader\LoaderInterface;
use Composer\Repository\Vcs\VcsDriverInterface;
use Fxp\Composer\AssetPlugin\Package\LazyPackageInterface;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyAssetPackageLoader;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\IO\MockIO;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of lazy asset package loader.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class LazyAssetPackageLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LazyAssetPackageLoader
     */
    protected $lazyLoader;

    /**
     * @var LazyPackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lazyPackage;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetType;

    /**
     * @var LoaderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loader;

    /**
     * @var VcsDriverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $driver;

    /**
     * @var MockIO
     */
    protected $io;

    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    protected function setUp()
    {
        $this->lazyPackage = $this->getMock('Fxp\Composer\AssetPlugin\Package\LazyPackageInterface');
        $this->assetType = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');
        $this->loader = $this->getMock('Composer\Package\Loader\LoaderInterface');
        $this->driver = $this->getMock('Composer\Repository\Vcs\VcsDriverInterface');
        $this->dispatcher = $this->getMockBuilder('Composer\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();

        $this->lazyPackage
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('PACKAGE_NAME'));
        $this->lazyPackage
            ->expects($this->any())
            ->method('getUniqueName')
            ->will($this->returnValue('PACKAGE_NAME-1.0.0.0'));
        $this->lazyPackage
            ->expects($this->any())
            ->method('getPrettyVersion')
            ->will($this->returnValue('1.0'));
        $this->lazyPackage
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0.0'));

        $versionConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface');
        $versionConverter->expects($this->any())
            ->method('convertVersion')
            ->will($this->returnValue('VERSION_CONVERTED'));
        $versionConverter->expects($this->any())
            ->method('convertRange')
            ->will($this->returnCallback(function ($value) {
                return $value;
            }));
        $packageConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface');
        /* @var LazyPackageInterface $lasyPackage */
        $lasyPackage = $this->lazyPackage;
        $packageConverter->expects($this->any())
            ->method('convert')
            ->will($this->returnCallback(function ($value) use ($lasyPackage) {
                $value['version'] = $lasyPackage->getPrettyVersion();
                $value['version_normalized'] = $lasyPackage->getVersion();

                return $value;
            }));
        $this->assetType->expects($this->any())
            ->method('getComposerVendorName')
            ->will($this->returnValue('ASSET'));
        $this->assetType->expects($this->any())
            ->method('getComposerType')
            ->will($this->returnValue('ASSET_TYPE'));
        $this->assetType->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue('ASSET.json'));
        $this->assetType->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($versionConverter));
        $this->assetType->expects($this->any())
            ->method('getPackageConverter')
            ->will($this->returnValue($packageConverter));

        $this->driver
            ->expects($this->any())
            ->method('getDist')
            ->will($this->returnCallback(function ($value) {
                return array(
                    'type' => 'vcs',
                    'url' => 'http://foobar.tld/dist/'.$value,
                );
            }));
        $this->driver
            ->expects($this->any())
            ->method('getSource')
            ->will($this->returnCallback(function ($value) {
                return array(
                    'type' => 'vcs',
                    'url' => 'http://foobar.tld/source/'.$value,
                );
            }));
    }

    protected function tearDown()
    {
        $this->lazyPackage = null;
        $this->assetType = null;
        $this->loader = null;
        $this->driver = null;
        $this->io = null;
        $this->dispatcher = null;
        $this->lazyLoader = null;
    }

    public function testMissingAssetType()
    {
        $this->setExpectedException('InvalidArgumentException', 'The "assetType" property must be defined');

        $loader = $this->createLazyLoader('TYPE');
        $loader->load($this->lazyPackage);
    }

    public function testMissingLoader()
    {
        $this->setExpectedException('InvalidArgumentException', 'The "loader" property must be defined');

        /* @var AssetTypeInterface $assetType */
        $assetType = $this->assetType;
        $loader = $this->createLazyLoader('TYPE');
        $loader->setAssetType($assetType);
        $loader->load($this->lazyPackage);
    }

    public function testMissingDriver()
    {
        $this->setExpectedException('InvalidArgumentException', 'The "driver" property must be defined');

        /* @var AssetTypeInterface $assetType */
        $assetType = $this->assetType;
        /* @var LoaderInterface $cLoader */
        $cLoader = $this->loader;
        /* @var LazyPackageInterface $lazyPackage */
        $lazyPackage = $this->lazyPackage;
        $loader = $this->createLazyLoader('TYPE');
        $loader->setAssetType($assetType);
        $loader->setLoader($cLoader);
        $loader->load($lazyPackage);
    }

    public function testMissingIo()
    {
        $this->setExpectedException('InvalidArgumentException', 'The "io" property must be defined');

        /* @var AssetTypeInterface $assetType */
        $assetType = $this->assetType;
        /* @var LoaderInterface $cLoader */
        $cLoader = $this->loader;
        /* @var VcsDriverInterface $driver */
        $driver = $this->driver;
        $loader = $this->createLazyLoader('TYPE');
        $loader->setAssetType($assetType);
        $loader->setLoader($cLoader);
        $loader->setDriver($driver);
        $loader->load($this->lazyPackage);
    }

    public function getConfigIo()
    {
        return array(
            array(false),
            array(true),
        );
    }

    /**
     * @param $verbose
     *
     * @dataProvider getConfigIo
     */
    public function testWithoutJsonFile($verbose)
    {
        /* @var \PHPUnit_Framework_MockObject_MockObject $driver */
        $driver = $this->driver;
        $driver
            ->expects($this->any())
            ->method('getComposerInformation')
            ->will($this->returnValue(false));

        /* @var \PHPUnit_Framework_MockObject_MockObject $loader */
        $loader = $this->loader;
        $loader
            ->expects($this->any())
            ->method('load')
            ->will($this->returnValue(false));

        $this->lazyLoader = $this->createLazyLoaderConfigured('TYPE', $verbose);
        $package = $this->lazyLoader->load($this->lazyPackage);

        $this->assertFalse($package);

        $filename = $this->assetType->getFilename();
        $validOutput = array('');

        if ($verbose) {
            $validOutput = array(
                'Reading '.$filename.' of <info>'.$this->lazyPackage->getName().'</info> (<comment>'.$this->lazyPackage->getPrettyVersion().'</comment>)',
                'Importing empty TYPE '.$this->lazyPackage->getPrettyVersion().' ('.$this->lazyPackage->getVersion().')',
                '',
            );
        }
        $this->assertSame($validOutput, $this->io->getTraces());

        $packageCache = $this->lazyLoader->load($this->lazyPackage);
        $this->assertFalse($packageCache);
        $this->assertSame($validOutput, $this->io->getTraces());
    }

    /**
     * @param $verbose
     *
     * @dataProvider getConfigIo
     */
    public function testWithJsonFile($verbose)
    {
        $arrayPackage = array(
            'name' => 'PACKAGE_NAME',
            'version' => '1.0',
        );

        $realPackage = $this->getMock('Composer\Package\CompletePackageInterface');
        $realPackage
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('PACKAGE_NAME'));
        $realPackage
            ->expects($this->any())
            ->method('getUniqueName')
            ->will($this->returnValue('PACKAGE_NAME-1.0.0.0'));
        $realPackage
            ->expects($this->any())
            ->method('getPrettyVersion')
            ->will($this->returnValue('1.0'));
        $realPackage
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.0.0'));

        /* @var \PHPUnit_Framework_MockObject_MockObject $driver */
        $driver = $this->driver;
        $driver
            ->expects($this->any())
            ->method('getComposerInformation')
            ->will($this->returnValue($arrayPackage));

        /* @var \PHPUnit_Framework_MockObject_MockObject $loader */
        $loader = $this->loader;
        $loader
            ->expects($this->any())
            ->method('load')
            ->will($this->returnValue($realPackage));

        $this->lazyLoader = $this->createLazyLoaderConfigured('TYPE', $verbose);
        $package = $this->lazyLoader->load($this->lazyPackage);

        $filename = $this->assetType->getFilename();
        $validOutput = array('');

        if ($verbose) {
            $validOutput = array(
                'Reading '.$filename.' of <info>'.$this->lazyPackage->getName().'</info> (<comment>'.$this->lazyPackage->getPrettyVersion().'</comment>)',
                'Importing TYPE'.' '.$this->lazyPackage->getPrettyVersion().' ('.$this->lazyPackage->getVersion().')',
                '',
            );
        }

        $this->assertInstanceOf('Composer\Package\CompletePackageInterface', $package);
        $this->assertSame($validOutput, $this->io->getTraces());

        $packageCache = $this->lazyLoader->load($this->lazyPackage);
        $this->assertInstanceOf('Composer\Package\CompletePackageInterface', $packageCache);
        $this->assertSame($package, $packageCache);
        $this->assertSame($validOutput, $this->io->getTraces());
    }

    public function getConfigIoForException()
    {
        return array(
            array('tag', false, 'Exception', '<warning>Skipped tag 1.0, MESSAGE</warning>'),
            array('tag', true, 'Exception', '<warning>Skipped tag 1.0, MESSAGE</warning>'),
            array('branch', false, 'Exception', '<error>Skipped branch 1.0, MESSAGE</error>'),
            array('branch', true, 'Exception', '<error>Skipped branch 1.0, MESSAGE</error>'),
            array('tag', false, 'Composer\Downloader\TransportException', '<warning>Skipped tag 1.0, no ASSET.json file was found</warning>'),
            array('tag', true, 'Composer\Downloader\TransportException', '<warning>Skipped tag 1.0, no ASSET.json file was found</warning>'),
            array('branch', false, 'Composer\Downloader\TransportException', '<error>Skipped branch 1.0, no ASSET.json file was found</error>'),
            array('branch', true, 'Composer\Downloader\TransportException', '<error>Skipped branch 1.0, no ASSET.json file was found</error>'),
        );
    }

    /**
     * @param string $type
     * @param bool   $verbose
     * @param string $exceptionClass
     * @param string $validTrace
     *
     * @dataProvider getConfigIoForException
     */
    public function testTagWithTransportException($type, $verbose, $exceptionClass, $validTrace)
    {
        /* @var \PHPUnit_Framework_MockObject_MockObject $loader */
        $loader = $this->loader;
        $loader
            ->expects($this->any())
            ->method('load')
            ->will($this->throwException(new $exceptionClass('MESSAGE')));

        $this->lazyLoader = $this->createLazyLoaderConfigured($type, $verbose);
        $package = $this->lazyLoader->load($this->lazyPackage);

        $this->assertFalse($package);

        $filename = $this->assetType->getFilename();
        $validOutput = array('');

        if ($verbose) {
            $validOutput = array(
                'Reading '.$filename.' of <info>'.$this->lazyPackage->getName().'</info> (<comment>'.$this->lazyPackage->getPrettyVersion().'</comment>)',
                'Importing empty '.$type.' '.$this->lazyPackage->getPrettyVersion().' ('.$this->lazyPackage->getVersion().')',
                $validTrace,
                '',
            );
        }
        $this->assertSame($validOutput, $this->io->getTraces());

        $packageCache = $this->lazyLoader->load($this->lazyPackage);
        $this->assertFalse($packageCache);
        $this->assertSame($validOutput, $this->io->getTraces());
    }

    /**
     * Creates the lazy asset package loader with full configuration.
     *
     * @param string $type
     * @param bool   $verbose
     *
     * @return LazyAssetPackageLoader
     */
    protected function createLazyLoaderConfigured($type, $verbose = false)
    {
        $this->io = new MockIO($verbose);

        /* @var AssetTypeInterface $assetType */
        $assetType = $this->assetType;
        /* @var LoaderInterface $cLoader */
        $cLoader = $this->loader;
        /* @var VcsDriverInterface $driver */
        $driver = $this->driver;
        /* @var EventDispatcher $dispatcher */
        $dispatcher = $this->dispatcher;
        $loader = $this->createLazyLoader($type);
        $loader->setAssetType($assetType);
        $loader->setLoader($cLoader);
        $loader->setDriver($driver);
        $loader->setIO($this->io);
        $loader->setEventDispatcher($dispatcher);

        return $loader;
    }

    /**
     * Creates the lazy asset package loader.
     *
     * @param string $type
     *
     * @return LazyAssetPackageLoader
     */
    protected function createLazyLoader($type)
    {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );

        return new LazyAssetPackageLoader($type, 'IDENTIFIER', $data);
    }
}
