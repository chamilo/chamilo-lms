<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Installer;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Installer for asset packages.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetInstaller extends LibraryInstaller
{
    /**
     * Constructor.
     *
     * @param IOInterface        $io
     * @param Composer           $composer
     * @param AssetTypeInterface $assetType
     * @param Filesystem         $filesystem
     */
    public function __construct(IOInterface $io, Composer $composer, AssetTypeInterface $assetType, Filesystem $filesystem = null)
    {
        parent::__construct($io, $composer, $assetType->getComposerType(), $filesystem);

        $extra = $composer->getPackage()->getExtra();
        if (!empty($extra['asset-installer-paths'][$this->type])) {
            $this->vendorDir = rtrim($extra['asset-installer-paths'][$this->type], '/');
        } else {
            $this->vendorDir = rtrim($this->vendorDir.'/'.$assetType->getComposerVendorName(), '/');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === $this->type;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPackageBasePath(PackageInterface $package)
    {
        $this->initializeVendorDir();

        list(, $name) = explode('/', $package->getPrettyName(), 2);

        return ($this->vendorDir ? $this->vendorDir.'/' : '').$name;
    }

    /**
     * {@inheritDoc}
     */
    protected function installCode(PackageInterface $package)
    {
        parent::installCode($package);

        $this->deleteIgnoredFiles($package);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateCode(PackageInterface $initial, PackageInterface $target)
    {
        parent::updateCode($initial, $target);

        $this->deleteIgnoredFiles($target);
    }

    /**
     * Deletes files defined in bower.json in section "ignore".
     *
     * @param PackageInterface $package
     */
    protected function deleteIgnoredFiles(PackageInterface $package)
    {
        $manager = IgnoreFactory::create($this->composer, $package, $this->getInstallPath($package));

        if ($manager->isEnabled() && !$manager->hasPattern()) {
            $this->addIgnorePatterns($manager, $package);
        }

        $manager->cleanup();
    }

    /**
     * Add ignore patterns in the manager.
     *
     * @param IgnoreManager    $manager The ignore manager instance
     * @param PackageInterface $package The package instance
     */
    protected function addIgnorePatterns(IgnoreManager $manager, PackageInterface $package)
    {
        // override this method
    }
}
