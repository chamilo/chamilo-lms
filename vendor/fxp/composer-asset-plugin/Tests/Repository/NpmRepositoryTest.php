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

use Composer\Config;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Fxp\Composer\AssetPlugin\Repository\NpmRepository;

/**
 * Tests of NPM repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmRepositoryTest extends AbstractAssetsRepositoryTest
{
    /**
     * {@inheritDoc}
     */
    protected function getType()
    {
        return 'npm';
    }

    /**
     * {@inheritDoc}
     */
    protected function getRegistry(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null)
    {
        return new NpmRepository($repoConfig, $io, $config, $eventDispatcher);
    }

    /**
     * {@inheritDoc}
     */
    protected function getMockPackageForVcsConfig()
    {
        return array(
            'repository' => array(
                'type' => 'vcs',
                'url' => 'http://foo.tld',
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getMockSearchResult($name = 'mock-package')
    {
        return array();
    }

    public function testWhatProvidesWithCamelcasePackageName()
    {
        $name = $this->getType().'-asset/CamelCasePackage';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->throwException(new TransportException('Package not found', 404)));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->rm->getRepositories());
    }

    public function testWatProvidesWithoutRepositoryUrl()
    {
        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array(
                'repository' => array(
                    'type' => 'vcs',
                ),
                'versions' => array(
                    '1.0.0' => array(
                        'name' => 'foobar',
                        'version' => '0.0.1',
                        'dependencies' => array(),
                        'dist' => array(
                            'shasum' => '1d408b3fdb76923b9543d96fb4c9dfd535d9cb5d',
                            'tarball' => 'http://registry.tld/foobar/-/foobar-1.0.0.tgz',
                        ),
                    ),
                ),
            ))));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }

    public function testWatProvidesWithoutRepositoryUrlAndWithoutVersions()
    {
        $this->setExpectedException('Fxp\Composer\AssetPlugin\Exception\InvalidCreateRepositoryException', '"repository.url" parameter of "foobar"');

        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array())));

        $this->assertCount(0, $this->rm->getRepositories());

        $this->registry->whatProvides($this->pool, $name);
    }

    public function testWhatProvidesWithGitPlusHttpsUrl()
    {
        $name = $this->getType().'-asset/existing';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array(
                'repository' => array(
                    'type' => 'vcs',
                    'url' => 'git+https://foo.tld',
                ),
            ))));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }
}
