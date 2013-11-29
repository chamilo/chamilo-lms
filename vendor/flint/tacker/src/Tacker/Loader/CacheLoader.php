<?php

namespace Tacker\Loader;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\ConfigCache;
use Tacker\ResourceCollection;

/**
 * @package Tacker
 */
class CacheLoader extends \Symfony\Component\Config\Loader\Loader
{
    protected $loader;
    protected $resources;
    protected $debug = false;
    protected $cacheDir;

    public function __construct(LoaderInterface $loader, ResourceCollection $resources)
    {
        $this->loader = $loader;
        $this->resources = $resources;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function load($resource, $type = null)
    {
        $cache = new ConfigCache(sprintf('%s/%s.php', $this->cacheDir, crc32($resource)), $this->debug);

        if (!$cache->isFresh()) {
            $parameters = $this->loader->load($resource);
        }

        if ($this->cacheDir && isset($parameters)) {
            $cache->write('<?php $parameters = ' . var_export($parameters, true) . ';', $this->resources->all());
        }

        if (!isset($parameters)) {
            require (string) $cache;
        }

        return $parameters;
    }

    public function supports($resource, $type = null)
    {
        return $this->loader->supports($resource, $type);
    }
}
