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
use Composer\DependencyResolver\Pool;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract assets repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetsRepository extends ComposerRepository
{
    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * @var RepositoryManager
     */
    protected $rm;

    /**
     * @var AssetVcsRepository[]
     */
    protected $repos;

    /**
     * @var bool
     */
    protected $searchable;

    /**
     * @var bool
     */
    protected $fallbackProviders;

    /**
     * @var RepositoryManager
     */
    protected $repositoryManager;

    /**
     * @var VcsPackageFilter
     */
    protected $packageFilter;

    /**
     * Constructor.
     *
     * @param array           $repoConfig
     * @param IOInterface     $io
     * @param Config          $config
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null)
    {
        $repoConfig = array_merge($repoConfig, array(
            'url' => $this->getUrl(),
        ));
        $this->repositoryManager = $repoConfig['repository-manager'];

        parent::__construct($repoConfig, $io, $config, $eventDispatcher);

        $this->assetType = Assets::createType($this->getType());
        $this->lazyProvidersUrl = $this->getPackageUrl();
        $this->providersUrl = $this->lazyProvidersUrl;
        $this->searchUrl = $this->getSearchUrl();
        $this->hasProviders = true;
        $this->rm = $repoConfig['repository-manager'];
        $this->packageFilter = isset($repoConfig['vcs-package-filter'])
            ? $repoConfig['vcs-package-filter']
            : null;
        $this->repos = array();
        $this->searchable = (bool) $this->getOption($repoConfig['asset-options'], 'searchable', true);
        $this->fallbackProviders = false;
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $mode = 0)
    {
        if (!$this->searchable) {
            return array();
        }

        $url = str_replace('%query%', urlencode(Util::cleanPackageName($query)), $this->searchUrl);
        $hostname = (string) parse_url($url, PHP_URL_HOST) ?: $url;
        $json = (string) $this->rfs->getContents($hostname, $url, false);
        $data = JsonFile::parseJson($json, $url);
        $results = array();

        /* @var array $item */
        foreach ($data as $item) {
            $results[] = $this->createSearchItem($item);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function whatProvides(Pool $pool, $name)
    {
        if (null !== $provides = $this->findWhatProvides($name)) {
            return $provides;
        }

        try {
            $repoName = Util::convertAliasName($name);
            $packageName = Util::cleanPackageName($repoName);
            $packageUrl = str_replace('%package%', $packageName, $this->lazyProvidersUrl);
            $cacheName = $packageName.'-'.sha1($packageName).'-package.json';
            $data = $this->fetchFile($packageUrl, $cacheName);
            $repo = $this->createVcsRepositoryConfig($data, Util::cleanPackageName($name));
            $repo['vcs-package-filter'] = $this->packageFilter;

            Util::addRepository($this->io, $this->rm, $this->repos, $name, $repo, $pool);

            $this->providers[$name] = array();
        } catch (\Exception $ex) {
            $this->whatProvidesManageException($pool, $name, $ex);
        }

        return $this->providers[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimalPackages()
    {
        return array();
    }

    /**
     * Finds what provides in cache or return empty array if the
     * name is not a asset package.
     *
     * @param string $name
     *
     * @return array|null
     */
    protected function findWhatProvides($name)
    {
        $assetPrefix = $this->assetType->getComposerVendorName().'/';

        if (false === strpos($name, $assetPrefix)) {
            return array();
        }

        if (isset($this->providers[$name])) {
            return $this->providers[$name];
        }

        $data = null;
        if ($this->hasVcsRepository($name)) {
            $this->providers[$name] = array();
            $data = $this->providers[$name];
        }

        return $data;
    }

    /**
     * Checks if the package vcs repository is already include in repository manager.
     *
     * @param string $name The package name of the vcs repository
     *
     * @return bool
     */
    protected function hasVcsRepository($name)
    {
        foreach ($this->repositoryManager->getRepositories() as $mRepo) {
            if ($mRepo instanceof AssetVcsRepository
                    && $name === $mRepo->getComposerPackageName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadRootServerFile()
    {
        return array(
            'providers' => array(),
        );
    }

    /**
     * Gets the option.
     *
     * @param array  $options The options
     * @param string $key     The key
     * @param mixed  $default The default value
     *
     * @return mixed The option value or default value if key is not found
     */
    protected function getOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * Creates the search result item.
     *
     * @param array $item The item
     *
     * @return array An array('name' => '...', 'description' => '...')
     */
    protected function createSearchItem(array $item)
    {
        return array(
            'name' => $this->assetType->getComposerVendorName().'/'.$item['name'],
            'description' => null,
        );
    }

    /**
     * Manage exception for "whatProvides" method.
     *
     * @param Pool       $pool
     * @param string     $name
     * @param \Exception $exception
     *
     * @throws \Exception When exception is not a TransportException instance
     */
    protected function whatProvidesManageException(Pool $pool, $name, \Exception $exception)
    {
        if ($exception instanceof TransportException) {
            $this->fallbackWathProvides($pool, $name, $exception);

            return;
        }

        throw $exception;
    }

    /**
     * Searchs if the registry has a package with the same name exists with a
     * different camelcase.
     *
     * @param Pool               $pool
     * @param string             $name
     * @param TransportException $ex
     */
    protected function fallbackWathProvides(Pool $pool, $name, TransportException $ex)
    {
        $providers = array();

        if (404 === $ex->getCode() && !$this->fallbackProviders) {
            $this->fallbackProviders = true;
            $repoName = Util::convertAliasName($name);
            $results = $this->search($repoName);

            foreach ($results as $item) {
                if ($name === strtolower($item['name'])) {
                    $providers = $this->whatProvides($pool, $item['name']);
                    break;
                }
            }
        }

        $this->fallbackProviders = false;
        $this->providers[$name] = $providers;
    }

    /**
     * Gets the asset type name.
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * Gets the URL of repository.
     *
     * @return string
     */
    abstract protected function getUrl();

    /**
     * Gets the URL for get the package information.
     *
     * @return string
     */
    abstract protected function getPackageUrl();

    /**
     * Gets the URL for get the search result.
     *
     * @return string
     */
    abstract protected function getSearchUrl();

    /**
     * Creates a config of vcs repository.
     *
     * @param array  $data         The repository config
     * @param string $registryName The package name in asset registry
     *
     * @return array An array('type' => '...', 'url' => '...')
     */
    abstract protected function createVcsRepositoryConfig(array $data, $registryName = null);
}
