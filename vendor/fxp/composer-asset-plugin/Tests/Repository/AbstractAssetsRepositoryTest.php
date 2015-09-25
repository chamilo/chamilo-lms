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

use Composer\DependencyResolver\Pool;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Config;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\AbstractAssetsRepository;
use Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository;

/**
 * Abstract class for Tests of assets repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetsRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RepositoryManager
     */
    protected $rm;

    /**
     * @var AbstractAssetsRepository
     */
    protected $registry;

    /**
     * @var Pool
     */
    protected $pool;

    protected function setUp()
    {
        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isVerbose')
            ->will($this->returnValue(true));
        /* @var IOInterface $io */
        $config = new Config();
        $config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir().'/composer-test',
                'cache-repo-dir' => sys_get_temp_dir().'/composer-test-cache-repo',
            ),
        ));
        $rm = new RepositoryManager($io, $config);
        $rm->setRepositoryClass($this->getType().'-vcs', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\MockAssetRepository');
        $repoConfig = array(
            'repository-manager' => $rm,
            'asset-options' => array(
                'searchable' => true,
            ),
        );

        $this->io = $io;
        $this->config = $config;
        $this->rm = $rm;
        $this->registry = $this->getRegistry($repoConfig, $io, $config);
        $this->pool = $this->getMock('Composer\DependencyResolver\Pool');
    }

    protected function tearDown()
    {
        $this->io = null;
        $this->config = null;
        $this->rm = null;
        $this->registry = null;
        $this->pool = null;
    }

    /**
     * Gets the asset type.
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * Gets the asset registry.
     *
     * @param array           $repoConfig
     * @param IOInterface     $io
     * @param Config          $config
     * @param EventDispatcher $eventDispatcher
     *
     * @return AbstractAssetsRepository
     */
    abstract protected function getRegistry(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null);

    /**
     * Gets the mock package of asset for the config of VCS repository.
     *
     * @return array
     */
    abstract protected function getMockPackageForVcsConfig();

    /**
     * Gets the mock search result.
     *
     * @param string $name
     *
     * @return array
     */
    abstract protected function getMockSearchResult($name = 'mock-package');

    /**
     * Replaces the Remote file system of Registry by a mock.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function replaceRegistryRfsByMock()
    {
        $ref = new \ReflectionClass($this->registry);
        $pRef = $ref->getParentClass()->getParentClass();
        $pRfs = $pRef->getProperty('rfs');
        $pRfs->setAccessible(true);

        $rfs = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($this->io, $this->config))
            ->getMock();

        $pRfs->setValue($this->registry, $rfs);

        return $rfs;
    }

    public function testFindPackageMustBeAlwaysNull()
    {
        $this->assertNull($this->registry->findPackage('foobar', '0'));
    }

    public function testFindPackageMustBeAlwaysEmpty()
    {
        $this->assertCount(0, $this->registry->findPackages('foobar', '0'));
    }

    public function testGetPackagesNotBeUsed()
    {
        $this->setExpectedException('LogicException');

        $this->registry->getPackages();
    }

    public function testGetProviderNamesMustBeEmpty()
    {
        $this->assertCount(0, $this->registry->getProviderNames());
    }

    public function testGetMinimalPackagesMustBeAlwaysEmpty()
    {
        $this->assertCount(0, $this->registry->getMinimalPackages());
    }

    public function testWhatProvidesWithNotAssetName()
    {
        $this->assertCount(0, $this->registry->whatProvides($this->pool, 'foo/bar'));
    }

    public function testWhatProvidesWithNonExistentPackage()
    {
        $name = $this->getType().'-asset/non-existent';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->throwException(new TransportException('Package not found')));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithExistingPackage()
    {
        $name = $this->getType().'-asset/existing';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockPackageForVcsConfig())));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithExistingAliasPackage()
    {
        $name = $this->getType().'-asset/existing-1.0';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockPackageForVcsConfig())));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithCamelcasePackageName()
    {
        $assetName = 'CamelCasePackage';
        $name = $this->getType().'-asset/'.strtolower($assetName);
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->at(0))
            ->method('getContents')
            ->will($this->throwException(new TransportException('Package not found', 404)));
        $rfs->expects($this->at(1))
            ->method('getContents')
            ->will($this->throwException(new TransportException('Package not found', 404)));
        $rfs->expects($this->at(2))
            ->method('getContents')
            ->will($this->throwException(new TransportException('Package not found', 404)));
        $rfs->expects($this->at(3))
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockSearchResult($assetName))));
        $rfs->expects($this->at(4))
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockPackageForVcsConfig())));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }

    public function testSearch()
    {
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockSearchResult())));

        $result = $this->registry->search('query');
        $this->assertCount(count($this->getMockSearchResult()), $result);
    }

    public function testSearchWithAssetComposerPrefix()
    {
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockSearchResult())));

        $result = $this->registry->search($this->getType().'-asset/query');
        $this->assertCount(count($this->getMockSearchResult()), $result);
    }

    public function testSearchWithSearchDisabled()
    {
        $repoConfig = array(
            'repository-manager' => $this->rm,
            'asset-options' => array(
                'searchable' => false,
            ),
        );
        $this->registry = $this->getRegistry($repoConfig, $this->io, $this->config);

        $this->assertCount(0, $this->registry->search('query'));
    }

    public function testOverridingVcsRepositoryConfig()
    {
        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode($this->getMockPackageForVcsConfig())));

        $repo = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->any())
            ->method('getComposerPackageName')
            ->will($this->returnValue($name));

        /* @var AssetVcsRepository $repo */
        $this->rm->addRepository($repo);

        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
    }
}
