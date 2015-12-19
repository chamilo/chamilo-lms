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

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\LoaderInterface;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Converter\SemverConverter;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyAssetPackageLoader;
use Fxp\Composer\AssetPlugin\Package\Version\VersionParser;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract class for Asset VCS repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetVcsRepository extends VcsRepository
{
    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * @var VersionParser
     */
    protected $versionParser;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var string
     */
    protected $rootPackageVersion;

    /**
     * @var array|null
     */
    protected $rootData;

    /**
     * @var VcsPackageFilter|null
     */
    protected $filter;

    /**
     * Constructor.
     *
     * @param array           $repoConfig
     * @param IOInterface     $io
     * @param Config          $config
     * @param EventDispatcher $dispatcher
     * @param array           $drivers
     */
    public function __construct(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $dispatcher = null, array $drivers = null)
    {
        $drivers = $drivers ?: Assets::getVcsDrivers();
        $assetType = substr($repoConfig['type'], 0, strpos($repoConfig['type'], '-'));
        $assetType = Assets::createType($assetType);
        $repoConfig['asset-type'] = $assetType->getName();
        $repoConfig['filename'] = $assetType->getFilename();
        $this->assetType = $assetType;
        $this->dispatcher = $dispatcher;
        $this->filter = isset($repoConfig['vcs-package-filter'])
                && $repoConfig['vcs-package-filter'] instanceof VcsPackageFilter
            ? $repoConfig['vcs-package-filter']
            : null;

        parent::__construct($repoConfig, $io, $config, $dispatcher, $drivers);
    }

    /**
     * Gets the package name of this repository.
     *
     * @return string
     */
    public function getComposerPackageName()
    {
        if (null === $this->packages) {
            $this->initialize();
        }

        return $this->assetType->formatComposerName($this->packageName);
    }

    /**
     * Initializes the driver.
     *
     * @return VcsDriverInterface
     *
     * @throws \InvalidArgumentException When not driver found.
     */
    protected function initDriver()
    {
        $driver = $this->getDriver();
        if (!$driver) {
            throw new \InvalidArgumentException('No driver found to handle Asset VCS repository '.$this->url);
        }

        return $driver;
    }

    /**
     * Initializes the version parser and loader.
     */
    protected function initLoader()
    {
        $this->versionParser = new VersionParser();

        if (!$this->loader) {
            $this->loader = new ArrayLoader($this->versionParser);
        }
    }

    /**
     * Initializes the root identifier.
     *
     * @param VcsDriverInterface $driver
     */
    protected function initRootIdentifier(VcsDriverInterface $driver)
    {
        try {
            if ($driver->hasComposerFile($driver->getRootIdentifier())) {
                $data = $driver->getComposerInformation($driver->getRootIdentifier());
                $sc = new SemverConverter();
                $this->rootPackageVersion = !empty($data['version'])
                    ? $sc->convertVersion(ltrim($data['version'], '^~'))
                    : null;
                $this->rootData = $data;

                if (null === $this->packageName) {
                    $this->packageName = !empty($data['name']) ? $data['name'] : null;
                }
            }
        } catch (\Exception $e) {
            if ($this->verbose) {
                $this->io->write('<error>Skipped parsing '.$driver->getRootIdentifier().', '.$e->getMessage().'</error>');
            }
        }
    }

    /**
     * Creates the package name with the composer prefix and the asset package name,
     * or only with the URL.
     *
     * @return string The package name
     */
    protected function createPackageName()
    {
        if (null === $this->packageName) {
            return $this->url;
        }

        return sprintf('%s/%s', $this->assetType->getComposerVendorName(), $this->packageName);
    }

    /**
     * Creates the mock of package config.
     *
     * @param string $name    The package name
     * @param string $version The version
     *
     * @return array The package config
     */
    protected function createMockOfPackageConfig($name, $version)
    {
        return array(
            'name' => $name,
            'version' => $version,
            'type' => $this->assetType->getComposerType(),
        );
    }

    /**
     * Creates the lazy loader of package.
     *
     * @param string             $type
     * @param string             $identifier
     * @param array              $packageData
     * @param VcsDriverInterface $driver
     *
     * @return LazyAssetPackageLoader
     */
    protected function createLazyLoader($type, $identifier, array $packageData, VcsDriverInterface $driver)
    {
        $lazyLoader = new LazyAssetPackageLoader($type, $identifier, $packageData);
        $lazyLoader->setAssetType($this->assetType);
        $lazyLoader->setLoader($this->loader);
        $lazyLoader->setDriver(clone $driver);
        $lazyLoader->setIO($this->io);
        $lazyLoader->setEventDispatcher($this->dispatcher);

        return $lazyLoader;
    }

    /**
     * Pre process the data of package before the conversion to Package instance.
     *
     * @param array $data
     *
     * @return array
     */
    protected function preProcessAsset(array $data)
    {
        $vcsRepos = array();

        // keep the name of the main identifier for all packages
        $data['name'] = $this->packageName ?: $data['name'];
        $data = $this->assetType->getPackageConverter()->convert($data, $vcsRepos);

        return (array) $data;
    }

    /**
     * Override the branch alias extra config of the current package.
     *
     * @param PackageInterface $package         The current package
     * @param string           $aliasNormalized The alias version normalizes
     * @param string           $branch          The branch name
     *
     * @return PackageInterface
     */
    protected function overrideBranchAliasConfig(PackageInterface $package, $aliasNormalized, $branch)
    {
        if ($package instanceof Package && false === strpos('dev-', $aliasNormalized)) {
            $extra = $package->getExtra();
            $extra['branch-alias'] = array(
                'dev-'.$branch => $this->rootPackageVersion.'-dev',
            );
            $this->injectExtraConfig($package, $extra);
        }

        return $package;
    }

    /**
     * Add the alias packages.
     *
     * @param PackageInterface $package         The current package
     * @param string           $aliasNormalized The alias version normalizes
     *
     * @return PackageInterface
     */
    protected function addPackageAliases(PackageInterface $package, $aliasNormalized)
    {
        $alias = new AliasPackage($package, $aliasNormalized, $this->rootPackageVersion);
        $this->addPackage($alias);

        if (false === strpos('dev-', $aliasNormalized)) {
            $alias = new AliasPackage($package, $aliasNormalized.'-dev', $this->rootPackageVersion);
            $this->addPackage($alias);
        }

        return $package;
    }

    /**
     * Inject the overriding extra config in the current package.
     *
     * @param PackageInterface $package The package
     * @param array            $extra   The new extra config
     */
    private function injectExtraConfig(PackageInterface $package, array $extra)
    {
        $ref = new \ReflectionClass($package);
        $met = $ref->getProperty('extra');
        $met->setAccessible(true);
        $met->setValue($package, $extra);
    }
}
