<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Composer\Installer\InstallationManager;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Package\LinkConstraint\MultiConstraint;
use Composer\Package\Loader\ArrayLoader;
use Composer\Repository\InstalledFilesystemRepository;
use Fxp\Composer\AssetPlugin\Package\Version\VersionParser;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Filters the asset packages imported into VCS repository to optimize
 * performance when getting the informations of packages.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VcsPackageFilter
{
    /**
     * @var RootPackageInterface
     */
    protected $package;

    /**
     * @var InstallationManager
     */
    protected $installationManager;

    /**
     * @var InstalledFilesystemRepository
     */
    protected $installedRepository;

    /**
     * @var VersionParser
     */
    protected $versionParser;

    /**
     * @var ArrayLoader
     */
    protected $arrayLoader;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var array
     */
    protected $requires;

    /**
     * Constructor.
     *
     * @param RootPackageInterface               $package             The root package
     * @param InstallationManager                $installationManager The installation manager
     * @param InstalledFilesystemRepository|null $installedRepository The installed repository
     */
    public function __construct(RootPackageInterface $package, InstallationManager $installationManager, InstalledFilesystemRepository $installedRepository = null)
    {
        $this->package = $package;
        $this->installationManager = $installationManager;
        $this->installedRepository = $installedRepository;
        $this->versionParser = new VersionParser();
        $this->arrayLoader = new ArrayLoader();
        $this->enabled = true;

        $this->initialize();
    }

    /**
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    /**
     * Check if the filter is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Check if the version must be skipped.
     *
     * @param AssetTypeInterface $assetType The asset type
     * @param string             $name      The composer package name
     * @param string             $version   The version
     *
     * @return bool
     */
    public function skip(AssetTypeInterface $assetType, $name, $version)
    {
        try {
            $cVersion = $assetType->getVersionConverter()->convertVersion($version);
            $normalizedVersion = $this->versionParser->normalize($cVersion);
        } catch (\Exception $ex) {
            return true;
        }

        if (false !== $this->skipByPattern() && $this->forceSkipVersion($normalizedVersion)) {
            return true;
        }

        return $this->doSkip($name, $normalizedVersion);
    }

    /**
     * Do check if the version must be skipped.
     *
     * @param string $name              The composer package name
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function doSkip($name, $normalizedVersion)
    {
        if (!isset($this->requires[$name])) {
            return false;
        }

        /* @var Link $require */
        $require = $this->requires[$name];

        return !$this->satisfy($require, $normalizedVersion) && $this->isEnabled();
    }

    /**
     * Check if the require dependency has a satisfactory version and stability.
     *
     * @param Link   $require           The require link defined in root package
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function satisfy(Link $require, $normalizedVersion)
    {
        return $this->satisfyVersion($require, $normalizedVersion)
            && $this->satisfyStability($require, $normalizedVersion);
    }

    /**
     * Check if the filter must be skipped the version by pattern or not.
     *
     * @return string|false Return the pattern or FALSE for disable the feature
     */
    protected function skipByPattern()
    {
        $extra = $this->package->getExtra();

        if (!array_key_exists('asset-pattern-skip-version', $extra)) {
            $extra['asset-pattern-skip-version'] = '(-patch)';
        }

        if (is_string($extra['asset-pattern-skip-version'])) {
            return trim($extra['asset-pattern-skip-version'], '/');
        }

        return false;
    }

    /**
     * Check if the require package version must be skipped or not.
     *
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function forceSkipVersion($normalizedVersion)
    {
        return (bool) preg_match('/'.$this->skipByPattern().'/', $normalizedVersion);
    }

    /**
     * Check if the require dependency has a satisfactory version.
     *
     * @param Link   $require           The require link defined in root package
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function satisfyVersion(Link $require, $normalizedVersion)
    {
        $constraintSame = $this->versionParser->parseConstraints($normalizedVersion);
        $sameVersion = (bool) $require->getConstraint()->matches($constraintSame);

        $normalizedVersion = FilterUtil::getVersionConstraint($normalizedVersion, $this->versionParser);
        $constraint = FilterUtil::getVersionConstraint($normalizedVersion, $this->versionParser);

        return (bool) $require->getConstraint()->matches($constraint) || $sameVersion;
    }

    /**
     * Check if the require dependency has a satisfactory stability.
     *
     * @param Link   $require           The require link defined in root package
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function satisfyStability(Link $require, $normalizedVersion)
    {
        $requireStability = $this->getRequireStability($require);
        $stability = $this->versionParser->parseStability($normalizedVersion);

        return Package::$stabilities[$stability] <= Package::$stabilities[$requireStability];
    }

    /**
     * Get the minimum stability for the require dependency defined in root package.
     *
     * @param Link $require The require link defined in root package
     *
     * @return string The minimum stability
     */
    protected function getRequireStability(Link $require)
    {
        $prettyConstraint = $require->getPrettyConstraint();
        $stabilities = Package::$stabilities;

        if (preg_match_all('/@('.implode('|', array_keys($stabilities)).')/', $prettyConstraint, $matches)) {
            return FilterUtil::findInlineStabilities($matches[1], $this->versionParser);
        }

        return FilterUtil::getMinimumStabilityFlag($this->package, $require);
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->requires = array_merge(
            $this->package->getRequires(),
            $this->package->getDevRequires()
        );

        if (null !== $this->installedRepository
                && FilterUtil::checkExtraOption($this->package, 'asset-optimize-with-installed-packages')) {
            $this->initInstalledPackages();
        }
    }

    /**
     * Initialize the installed package.
     */
    private function initInstalledPackages()
    {
        /* @var PackageInterface $package */
        foreach ($this->installedRepository->getPackages() as $package) {
            $operator = $this->getFilterOperator($package);
            /* @var Link $link */
            if (method_exists($this->arrayLoader, 'parseLinks')) {
                $link = current($this->arrayLoader->parseLinks($this->package->getName(), $this->package->getVersion(), 'installed', array($package->getName() => $operator.$package->getPrettyVersion())));
            } else {
                $link = current($this->versionParser->parseLinks($this->package->getName(), $this->package->getVersion(), 'installed', array($package->getName() => $operator.$package->getPrettyVersion())));
            }
            $link = $this->includeRootConstraint($package, $link);

            $this->requires[$package->getName()] = $link;
        }
    }

    /**
     * Include the constraint of root dependency version in the constraint
     * of installed package.
     *
     * @param PackageInterface $package The installed package
     * @param Link             $link    The link contained installed constraint
     *
     * @return Link The link with root and installed version constraint
     */
    private function includeRootConstraint(PackageInterface $package, Link $link)
    {
        if (isset($this->requires[$package->getName()])) {
            /* @var Link $rLink */
            $rLink = $this->requires[$package->getName()];
            $useConjunctive = FilterUtil::checkExtraOption($this->package, 'asset-optimize-with-conjunctive');
            $constraint = new MultiConstraint(array($rLink->getConstraint(), $link->getConstraint()), $useConjunctive);
            $link = new Link($rLink->getSource(), $rLink->getTarget(), $constraint, 'installed', $constraint->getPrettyString());
        }

        return $link;
    }

    /**
     * Get the filter root constraint operator.
     *
     * @param PackageInterface $package
     *
     * @return string
     */
    private function getFilterOperator(PackageInterface $package)
    {
        return $this->installationManager->isPackageInstalled($this->installedRepository, $package)
            ? '>'
            : '>=';
    }
}
