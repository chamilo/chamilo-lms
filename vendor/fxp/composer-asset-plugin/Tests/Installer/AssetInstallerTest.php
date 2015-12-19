<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Installer\AssetInstaller;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of asset installer.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetInstallerTest extends \PHPUnit_Framework_TestCase
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
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $type;

    protected function setUp()
    {
        $this->io = $this->getMock('Composer\IO\IOInterface');
        $config = $this->getMock('Composer\Config');
        $config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) {
                switch ($key) {
                    case 'cache-repo-dir':
                        return sys_get_temp_dir().'/composer-test-repo-cache';
                    case 'vendor-dir':
                        return sys_get_temp_dir().'/composer-test/vendor';
                }

                return;
            }));

        $this->package = $this->getMock('Composer\Package\PackageInterface');

        $this->composer = $this->getMock('Composer\Composer');
        $this->composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));
        $this->composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $this->type = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');
        $this->type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));
        $this->type->expects($this->any())
            ->method('getComposerVendorName')
            ->will($this->returnValue('foo-asset'));
        $this->type->expects($this->any())
            ->method('getComposerType')
            ->will($this->returnValue('foo-asset-library'));
        $this->type->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue('foo.json'));
        $this->type->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface')));
        $this->type->expects($this->any())
            ->method('getPackageConverter')
            ->will($this->returnValue($this->getMock('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface')));
    }

    protected function tearDown()
    {
        $this->package = null;
        $this->composer = null;
        $this->io = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/composer-test-repo-cache');
        $fs->remove(sys_get_temp_dir().'/composer-test/vendor');
    }

    public function testDefaultVendorDir()
    {
        $installer = $this->createInstaller();
        $vendorDir = realpath(sys_get_temp_dir()).'/composer-test/vendor/'.$this->type->getComposerVendorName();
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testCustomFooDir()
    {
        $vendorDir = realpath(sys_get_temp_dir()).'/composer-test/web';
        $vendorDir = str_replace('\\', '/', $vendorDir);

        /* @var \PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->package;
        $package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array(
                'asset-installer-paths' => array(
                    $this->type->getComposerType() => $vendorDir,
                ),
            )));

        $installer = $this->createInstaller();

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testInstall()
    {
        /* @var RootPackageInterface $rootPackage */
        $rootPackage = $this->createRootPackageMock();
        /* @var IOInterface $io */
        $io = $this->io;
        /* @var AssetTypeInterface $type */
        $type = $this->type;
        $vendorDir = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'composer-test'.DIRECTORY_SEPARATOR.'vendor';

        $this->composer->setPackage($rootPackage);

        $dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->composer->expects($this->any())
            ->method('getDownloadManager')
            ->will($this->returnValue($dm));

        $library = new AssetInstaller($io, $this->composer, $type);
        /* @var \PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createPackageMock('foo-asset/package');

        /* @var PackageInterface $package */
        $packageDir = $vendorDir.'/'.$package->getPrettyName();

        $dm->expects($this->once())
            ->method('download')
            ->with($package, $vendorDir.DIRECTORY_SEPARATOR.'foo-asset/package');

        $repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');
        $repository->expects($this->once())
            ->method('addPackage')
            ->with($package);

        /* @var InstalledRepositoryInterface $repository */
        $library->install($repository, $package);
        $this->assertFileExists($vendorDir, 'Vendor dir should be created');

        $this->ensureDirectoryExistsAndClear($packageDir);
    }

    /**
     * Creates the asset installer.
     *
     * @return AssetInstaller
     */
    protected function createInstaller()
    {
        /* @var IOInterface $io */
        $io = $this->io;
        /* @var Composer $composer */
        $composer = $this->composer;
        /* @var AssetTypeInterface $type */
        $type = $this->type;

        return new AssetInstaller($io, $composer, $type);
    }

    /**
     * Creates the mock package.
     *
     * @param string $name
     *
     * @return PackageInterface
     */
    private function createPackageMock($name)
    {
        return $this->getMock('Composer\Package\Package', null, array($name, '1.0.0.0', '1.0.0'));
    }

    /**
     * @return RootPackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRootPackageMock()
    {
        $package = $this->getMockBuilder('Composer\Package\RootPackageInterface')
            ->setConstructorArgs(array(md5(mt_rand()), '1.0.0.0', '1.0.0'))
            ->getMock();

        $package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array()));

        return $package;
    }

    protected function ensureDirectoryExistsAndClear($directory)
    {
        $fs = new Filesystem();
        if (is_dir($directory)) {
            $fs->removeDirectory($directory);
        }
        mkdir($directory, 0777, true);
    }
}
