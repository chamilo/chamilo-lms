<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Package\Loader;

use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Loader\LoaderInterface;
use Composer\Repository\Vcs\VcsDriverInterface;
use Fxp\Composer\AssetPlugin\AssetEvents;
use Fxp\Composer\AssetPlugin\Event\VcsRepositoryEvent;
use Fxp\Composer\AssetPlugin\Package\LazyPackageInterface;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Lazy loader for asset package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class LazyAssetPackageLoader implements LazyLoaderInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $packageData;

    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var VcsDriverInterface
     */
    protected $driver;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var bool
     */
    protected $verbose;

    /**
     * @var array
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param string $identifier
     * @param string $type
     * @param array  $packageData
     */
    public function __construct($type, $identifier, array $packageData)
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->packageData = $packageData;
        $this->verbose = false;
        $this->cache = array();
    }

    /**
     * Sets the asset type.
     *
     * @param AssetTypeInterface $assetType
     */
    public function setAssetType(AssetTypeInterface $assetType)
    {
        $this->assetType = $assetType;
    }

    /**
     * Sets the laoder.
     *
     * @param LoaderInterface $loader
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Sets the driver.
     *
     * @param VcsDriverInterface $driver
     */
    public function setDriver(VcsDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Sets the IO.
     *
     * @param IOInterface $io
     */
    public function setIO(IOInterface $io)
    {
        $this->io = $io;
        $this->verbose = $io->isVerbose();
    }

    /**
     * Sets the event dispatcher.
     *
     * @param EventDispatcher $dispatcher
     */
    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function load(LazyPackageInterface $package)
    {
        if (isset($this->cache[$package->getUniqueName()])) {
            return $this->cache[$package->getUniqueName()];
        }
        $this->validateConfig();

        $filename = $this->assetType->getFilename();
        $msg = 'Reading '.$filename.' of <info>'.$package->getName().'</info> (<comment>'.$package->getPrettyVersion().'</comment>)';
        if ($this->verbose) {
            $this->io->write($msg);
        } else {
            $this->io->overwrite($msg, false);
        }

        $realPackage = $this->loadRealPackage($package);
        $this->cache[$package->getUniqueName()] = $realPackage;

        if (!$this->verbose) {
            $this->io->overwrite('', false);
        }

        return $realPackage;
    }

    /**
     * Validates the class config.
     *
     * @throws \InvalidArgumentException When the property of this class is not defined
     */
    protected function validateConfig()
    {
        foreach (array('assetType', 'loader', 'driver', 'io') as $property) {
            if (null === $this->$property) {
                throw new \InvalidArgumentException(sprintf('The "%s" property must be defined', $property));
            }
        }
    }

    /**
     * Loads the real package.
     *
     * @param LazyPackageInterface $package
     *
     * @return CompletePackageInterface|false
     */
    protected function loadRealPackage(LazyPackageInterface $package)
    {
        $realPackage = false;

        try {
            $data = $this->driver->getComposerInformation($this->identifier);
            $valid = is_array($data);
            $data = $this->preProcess($this->driver, $this->validateData($data), $this->identifier);

            if ($this->verbose) {
                $this->io->write('Importing '.($valid ? '' : 'empty ').$this->type.' '.$data['version'].' ('.$data['version_normalized'].')');
            }

            /* @var CompletePackageInterface $realPackage */
            $realPackage = $this->loader->load($data);
        } catch (\Exception $e) {
            if ($this->verbose) {
                $filename = $this->assetType->getFilename();
                $this->io->write('<'.$this->getIoTag().'>Skipped '.$this->type.' '.$package->getPrettyVersion().', '.($e instanceof TransportException ? 'no '.$filename.' file was found' : $e->getMessage()).'</'.$this->getIoTag().'>');
            }
        }
        $this->driver->cleanup();

        return $realPackage;
    }

    /**
     * @param array|bool $data
     *
     * @return array
     */
    protected function validateData($data)
    {
        return is_array($data) ? $data : array();
    }

    /**
     * Gets the tag name for IO.
     *
     * @return string
     */
    protected function getIoTag()
    {
        return 'branch' === $this->type ? 'error' : 'warning';
    }

    /**
     * Pre process the data of package before the conversion to Package instance.
     *
     * @param VcsDriverInterface $driver
     * @param array              $data
     * @param string             $identifier
     *
     * @return array
     */
    protected function preProcess(VcsDriverInterface $driver, array $data, $identifier)
    {
        $vcsRepos = array();
        $data = array_merge($data, $this->packageData);
        $data = $this->assetType->getPackageConverter()->convert($data, $vcsRepos);

        $this->dispatchAddVcsEvent($vcsRepos);

        if (!isset($data['dist'])) {
            $data['dist'] = $driver->getDist($identifier);
        }
        if (!isset($data['source'])) {
            $data['source'] = $driver->getSource($identifier);
        }

        return  (array) $data;
    }

    /**
     * Dispatches the vcs repositories event.
     *
     * @param array $vcsRepos
     */
    protected function dispatchAddVcsEvent(array $vcsRepos)
    {
        if (null !== $this->dispatcher) {
            $event = new VcsRepositoryEvent(AssetEvents::ADD_VCS_REPOSITORIES, $vcsRepos);
            $this->dispatcher->dispatch($event->getName(), $event);
        }
    }
}
