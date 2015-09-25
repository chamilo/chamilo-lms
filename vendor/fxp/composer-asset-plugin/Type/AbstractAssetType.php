<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Type;

use Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface;
use Fxp\Composer\AssetPlugin\Converter\SemverConverter;
use Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface;

/**
 * Abstract asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetType implements AssetTypeInterface
{
    /**
     * @var PackageConverterInterface
     */
    protected $packageConverter;

    /**
     * @var VersionConverterInterface
     */
    protected $versionConverter;

    /**
     * Constructor.
     *
     * @param PackageConverterInterface $packageConverter
     * @param VersionConverterInterface $versionConverter
     */
    public function __construct(PackageConverterInterface $packageConverter = null, VersionConverterInterface $versionConverter = null)
    {
        $this->packageConverter = !$packageConverter ? $this->createPackageConverter() : $packageConverter;
        $this->versionConverter = !$versionConverter ? new SemverConverter() : $versionConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerVendorName()
    {
        return $this->getName().'-asset';
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerType()
    {
        return $this->getName().'-asset-library';
    }

    /**
     * {@inheritDoc}
     */
    public function getFilename()
    {
        return $this->getName().'.json';
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageConverter()
    {
        return $this->packageConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function getVersionConverter()
    {
        return $this->versionConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function formatComposerName($name)
    {
        $prefix = $this->getComposerVendorName().'/';

        if (preg_match('/(\:\/\/)|\@/', $name) || 0 === strpos($name, $prefix)) {
            return $name;
        }

        return $prefix.$name;
    }

    /**
     * @return PackageConverterInterface
     */
    abstract protected function createPackageConverter();
}
