<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Repository\RepositoryManager;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\AssetEvents;
use Fxp\Composer\AssetPlugin\Event\VcsRepositoryEvent;
use Fxp\Composer\AssetPlugin\FxpAssetPlugin;

/**
 * Tests of asset plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpAssetPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FxpAssetPlugin
     */
    protected $plugin;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    protected function setUp()
    {
        $io = $this->getMock('Composer\IO\IOInterface');
        $config = $this->getMock('Composer\Config');
        $config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) {
                switch ($key) {
                    case 'cache-repo-dir':
                        return sys_get_temp_dir().'/composer-test-repo-cache';
                }

                return;
            }));
        $this->package = $this->getMock('Composer\Package\RootPackageInterface');
        $this->package->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue(array()));
        $this->package->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue(array()));

        /* @var IOInterface $io */
        /* @var Config $config */
        $rm = new RepositoryManager($io, $config);
        $im = new InstallationManager();

        $composer = $this->getMock('Composer\Composer');
        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($rm));
        $composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));
        $composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));
        $composer->expects($this->any())
            ->method('getInstallationManager')
            ->will($this->returnValue($im));

        $this->plugin = new FxpAssetPlugin();
        $this->composer = $composer;
        $this->io = $io;
    }

    protected function tearDown()
    {
        $this->plugin = null;
        $this->composer = null;
        $this->io = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/composer-test-repo-cache');
    }

    public function testAssetRepositories()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array()));

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(2, $repos);
        foreach ($repos as $repo) {
            $this->assertInstanceOf('Composer\Repository\ComposerRepository', $repo);
        }
    }

    /**
     * @dataProvider getDataForAssetVcsRepositories
     */
    public function testAssetVcsRepositories($type)
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array()));

        $this->plugin->activate($this->composer, $this->io);
        $rm = $this->composer->getRepositoryManager();
        $repo = $rm->createRepository($type, array(
            'type' => $type,
            'url' => 'http://foo.tld',
        ));

        $this->assertInstanceOf('Composer\Repository\VcsRepository', $repo);
    }

    public function getDataForAssetVcsRepositories()
    {
        return array(
            array('npm-vcs'),
            array('npm-git'),
            array('npm-github'),

            array('bower-vcs'),
            array('bower-git'),
            array('bower-github'),
        );
    }

    public function testAssetRepositoryWithValueIsNotArray()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                'invalid_repo',
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepositoryWithInvalidType()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array(),
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepositoryWithInvalidTypeFormat()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'invalid_type'),
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepositoryWithInvalidUrl()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'npm-vcs'),
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepository()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld'),
            ))));

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(3, $repos);
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[2]);
    }

    public function testAssetRepositoryWithAlreadyExistRepositoryName()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld'),
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld'),
            ))));

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(3, $repos);
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[2]);
    }

    public function testAssetPackageWithoutPackage()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'package'),
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetPackageWithInvalidPackage()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'package', 'package' => array('key' => 'value')),
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetPackageRepositories()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array(
                    'type' => 'package',
                    'package' => array(
                        'name' => 'foo',
                        'type' => 'ASSET-asset-library',
                        'version' => '0.0.0.0',
                        'dist' => array(
                            'url' => 'foo.tld/bar',
                            'type' => 'file',
                        ),
                    ),
                ),
            ))));

        $rm = $this->composer->getRepositoryManager();
        $rm->setRepositoryClass('package', 'Composer\Repository\PackageRepository');
        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(3, $repos);
        $this->assertInstanceOf('Composer\Repository\PackageRepository', $repos[2]);
    }

    public function testOptionsForAssetRegistryRepositories()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-registry-options' => array(
                'npm-option1' => 'value 1',
                'bower-option1' => 'value 2',
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testSubscribeEvents()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array()));

        $this->assertCount(3, $this->plugin->getSubscribedEvents());
        $this->assertCount(0, $this->composer->getRepositoryManager()->getRepositories());

        $event = new VcsRepositoryEvent(AssetEvents::ADD_VCS_REPOSITORIES, array(
            array('type' => 'npm-vcs', 'url' => 'http://foo.tld'),
        ));
        /* @var InstallerEvent $eventInstaller */
        $eventInstaller = $this->getMockBuilder('Composer\Installer\InstallerEvent')
            ->disableOriginalConstructor()
            ->getMock();
        /* @var CommandEvent|\PHPUnit_Framework_MockObject_MockObject $eventCommand */
        $eventCommand = $this->getMockBuilder('Composer\Plugin\CommandEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $eventCommand->expects($this->any())
            ->method('getCommandName')
            ->will($this->returnValue('show'));

        $this->plugin->activate($this->composer, $this->io);
        $this->assertCount(2, $this->composer->getRepositoryManager()->getRepositories());
        $this->plugin->onAddVcsRepositories($event);
        $this->plugin->onPluginCommand($eventCommand);
        $this->plugin->onPreDependenciesSolving($eventInstaller);
        $this->assertCount(3, $this->composer->getRepositoryManager()->getRepositories());
    }

    public function testAssetInstallers()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array()));

        $this->plugin->activate($this->composer, $this->io);
        $im = $this->composer->getInstallationManager();

        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Installer\BowerInstaller', $im->getInstaller('bower-asset-library'));
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Installer\AssetInstaller', $im->getInstaller('npm-asset-library'));
    }
}
