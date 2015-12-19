<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository;

use Composer\EventDispatcher\EventDispatcher;
use Composer\Config;
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Repository\InvalidRepositoryException;
use Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\IO\MockIO;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriver;

/**
 * Tests of asset vcs repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetVcsRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var MockIO
     */
    protected $io;

    /**
     * @var AssetVcsRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->config = new Config();
        /* @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Composer\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $dispatcher;
    }

    protected function tearDown()
    {
        $this->config = null;
        $this->dispatcher = null;
        $this->io = null;
        $this->repository = null;
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function getDefaultDrivers()
    {
        return array(
            array('npm-github', 'http://example.org/foo.git', 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver'),
            array('npm-git', 'http://example.org/foo.git', 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitDriver'),
            array('bower-github', 'http://example.org/foo.git', 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver'),
            array('bower-git', 'http://example.org/foo.git', 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitDriver'),
        );
    }

    /**
     * @dataProvider getDefaultDrivers
     */
    public function testDefaultConstructor($type, $url)
    {
        $this->init(false, $type, $url, '', false, array());
        $this->assertEquals(0, $this->repository->count());
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function getMockDrivers()
    {
        return array(
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriver'),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriver'),
        );
    }

    /**
     * @dataProvider getMockDrivers
     */
    public function testNotDriverFound($type, $url, $class)
    {
        $this->setExpectedException('InvalidArgumentException', 'No driver found to handle Asset VCS repository '.$url);

        $this->init(false, $type, $url, $class);
        $this->repository->getPackages();
    }

    /**
     * @dataProvider getMockDrivers
     */
    public function testWithoutValidPackage($type, $url, $class)
    {
        $this->setExpectedException('Composer\Repository\InvalidRepositoryException');

        $this->init(true, $type, $url, $class);
        $this->repository->getPackages();
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function getMockDriversSkipParsing()
    {
        return array(
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverSkipParsing', false),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverSkipParsing', false),
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverSkipParsing', true),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverSkipParsing', true),
        );
    }

    /**
     * @dataProvider getMockDriversSkipParsing
     */
    public function testSkipParsingFile($type, $url, $class, $verbose)
    {
        $validTraces = array('');
        if ($verbose) {
            $validTraces = array(
                '<error>Skipped parsing ROOT, MESSAGE with ROOT</error>',
                '',
            );
        }

        $this->init(true, $type, $url, $class, $verbose);

        try {
            $this->repository->getPackages();
        } catch (InvalidRepositoryException $e) {
            // for analysis the IO traces
        }
        $this->assertSame($validTraces, $this->io->getTraces());
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function getMockDriversWithVersions()
    {
        return array(
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithPackages', false),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithPackages', false),
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithPackages', true),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithPackages', true),
        );
    }

    /**
     * @dataProvider getMockDriversWithVersions
     */
    public function testRepositoryPackageName($type, $url, $class, $verbose)
    {
        $packageName = 'asset-package-name';
        $valid = str_replace('-mock', '-asset', $type).'/'.$packageName;

        $this->init(true, $type, $url, $class, $verbose, null, $packageName);

        $this->assertEquals($valid, $this->repository->getComposerPackageName());
    }

    /**
     * @dataProvider getMockDriversWithVersions
     */
    public function testWithTagsAndBranchs($type, $url, $class, $verbose)
    {
        $validPackageName = substr($type, 0, strpos($type, '-')).'-asset/foobar';
        $validTraces = array('');
        if ($verbose) {
            $validTraces = array(
                '<warning>Skipped tag invalid, invalid tag name</warning>',
                '',
            );
        }

        $this->init(true, $type, $url, $class, $verbose);

        /* @var PackageInterface[] $packages */
        $packages = $this->repository->getPackages();
        $this->assertCount(7, $packages);

        foreach ($packages as $package) {
            if ($package instanceof AliasPackage) {
                $package = $package->getAliasOf();
            }

            $this->assertInstanceOf('Composer\Package\CompletePackage', $package);
            $this->assertSame($validPackageName,  $package->getName());
        }

        $this->assertSame($validTraces, $this->io->getTraces());
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function getMockDriversWithVersionsAndWithoutName()
    {
        return array(
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithUrlPackages', false),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithUrlPackages', false),
            array('npm-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithUrlPackages', true),
            array('bower-mock', 'http://example.org/foo', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs\MockVcsDriverWithUrlPackages', true),
        );
    }

    /**
     * @dataProvider getMockDriversWithVersionsAndWithoutName
     */
    public function testWithTagsAndBranchsWithoutPackageName($type, $url, $class, $verbose)
    {
        $validPackageName = $url;
        $validTraces = array('');
        if ($verbose) {
            $validTraces = array(
                '<warning>Skipped tag invalid, invalid tag name</warning>',
                '',
            );
        }

        $this->init(true, $type, $url, $class, $verbose);

        /* @var PackageInterface[] $packages */
        $packages = $this->repository->getPackages();
        $this->assertCount(7, $packages);

        foreach ($packages as $package) {
            if ($package instanceof AliasPackage) {
                $package = $package->getAliasOf();
            }

            $this->assertInstanceOf('Composer\Package\CompletePackage', $package);
            $this->assertSame($validPackageName,  $package->getName());
        }

        $this->assertSame($validTraces, $this->io->getTraces());
    }

    /**
     * @dataProvider getMockDriversWithVersions
     */
    public function testWithTagsAndBranchsWithRegistryPackageName($type, $url, $class, $verbose)
    {
        $validPackageName = substr($type, 0, strpos($type, '-')).'-asset/registry-foobar';
        $validTraces = array('');
        if ($verbose) {
            $validTraces = array(
                '<warning>Skipped tag invalid, invalid tag name</warning>',
                '',
            );
        }

        $this->init(true, $type, $url, $class, $verbose, null, 'registry-foobar');

        /* @var PackageInterface[] $packages */
        $packages = $this->repository->getPackages();
        $this->assertCount(7, $packages);

        foreach ($packages as $package) {
            if ($package instanceof AliasPackage) {
                $package = $package->getAliasOf();
            }

            $this->assertInstanceOf('Composer\Package\CompletePackage', $package);
            $this->assertSame($validPackageName,  $package->getName());
        }

        $this->assertSame($validTraces, $this->io->getTraces());
    }

    /**
     * @dataProvider getMockDriversWithVersions
     */
    public function testWithFilterTags($type, $url, $class, $verbose)
    {
        $validPackageName = substr($type, 0, strpos($type, '-')).'-asset/registry-foobar';
        $validTraces = array('');
        if ($verbose) {
            $validTraces = array();
        }

        $filter = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter')
            ->disableOriginalConstructor()
            ->getMock();

        $filter->expects($this->any())
            ->method('skip')
            ->will($this->returnValue(true));

        /* @var VcsPackageFilter $filter */
        $this->init(true, $type, $url, $class, $verbose, null, 'registry-foobar', $filter);

        /* @var PackageInterface[] $packages */
        $packages = $this->repository->getPackages();
        $this->assertCount(5, $packages);

        foreach ($packages as $package) {
            if ($package instanceof AliasPackage) {
                $package = $package->getAliasOf();
            }

            $this->assertInstanceOf('Composer\Package\CompletePackage', $package);
            $this->assertSame($validPackageName,  $package->getName());
        }

        $this->assertSame($validTraces, $this->io->getTraces());
    }

    /**
     * @dataProvider getMockDrivers
     */
    public function testPackageWithRegistryVersions($type, $url, $class)
    {
        $registryPackages = array(
            new CompletePackage('package1', '0.1.0.0', '0.1'),
            new CompletePackage('package1', '0.2.0.0', '0.2'),
            new CompletePackage('package1', '0.3.0.0', '0.3'),
            new CompletePackage('package1', '0.4.0.0', '0.4'),
            new CompletePackage('package1', '0.5.0.0', '0.5'),
            new CompletePackage('package1', '0.6.0.0', '0.6'),
            new CompletePackage('package1', '0.7.0.0', '0.7'),
            new CompletePackage('package1', '0.8.0.0', '0.8'),
            new CompletePackage('package1', '0.9.0.0', '0.9'),
            new CompletePackage('package1', '1.0.0.0', '1.0'),
        );

        $this->init(true, $type, $url, $class, false, null, 'registry-foobar', null, $registryPackages);

        /* @var PackageInterface[] $packages */
        $packages = $this->repository->getPackages();
        $this->assertCount(10, $packages);
        $this->assertSame($registryPackages, $packages);
    }

    /**
     * Init the test.
     *
     * @param bool                  $supported
     * @param string                $type
     * @param string                $url
     * @param string                $class
     * @param bool                  $verbose
     * @param array|null            $drivers
     * @param string|null           $registryName
     * @param VcsPackageFilter|null $vcsPackageFilter
     * @param array                 $registryPackages
     */
    protected function init($supported, $type, $url, $class, $verbose = false, $drivers = null, $registryName = null, VcsPackageFilter $vcsPackageFilter = null, array $registryPackages = array())
    {
        MockVcsDriver::$supported = $supported;
        $driverType = substr($type, strpos($type, '-') + 1);
        $repoConfig = array('type' => $type, 'url' => $url, 'name' => $registryName, 'vcs-package-filter' => $vcsPackageFilter);

        if (null === $drivers) {
            $drivers = array(
                $driverType => $class,
            );
        }

        if (count($registryPackages) > 0) {
            $repoConfig['registry-versions'] = $registryPackages;
        }

        $this->io = $this->createIO($verbose);
        $this->repository = new AssetVcsRepository($repoConfig, $this->io, $this->config, $this->dispatcher, $drivers);
    }

    /**
     * @param bool $verbose
     *
     * @return MockIO
     */
    protected function createIO($verbose = false)
    {
        return new MockIO($verbose);
    }
}
