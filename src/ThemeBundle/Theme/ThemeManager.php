<?php
/**
 * ThemeManager.php
 * publisher
 * Date: 18.04.14
 */

namespace Chamilo\ThemeBundle\Theme;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;
use Assetic\Factory\Resource\FileResource;
use Chamilo\FoundationBundle\Util\DependencyResolverInterface;
use Symfony\Bundle\AsseticBundle\Config\AsseticResource;
use Symfony\Component\DependencyInjection\Container;
use Chamilo\FoundationBundle\Util\DependencyResolver;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * Class ThemeManager
 * @package Chamilo\ThemeBundle\Theme
 */
class ThemeManager
{
    /** @var  Container */
    protected $container;

    protected $stylesheets = [];

    protected $javascripts = [];

    protected $locations = [];

    protected $resolverClass;

    /**
     * ThemeManager constructor.
     * @param $container
     * @param null $resolverClass
     */
    public function __construct($container, $resolverClass = null)
    {
        $this->container     = $container;
        $this->resolverClass = $resolverClass ?: 'Chamilo\ThemeBundle\Util\DependencyResolver';
    }

    public function registerScript($id, $src, $deps = [], $location = "bottom")
    {
        if (!isset($this->javascripts[$id])) {
            $this->javascripts[$id] = [
                'src'      => $src,
                'deps'     => $deps,
                'location' => $location
            ];
        }
    }

    public function registerStyle($id, $src, $deps = [])
    {
        if (!isset($this->stylesheets[$id])) {
            $this->stylesheets[$id] = [
                'src'      => $src,
                'deps'     => $deps,
            ];
        }
    }

    public function getScripts($location = 'bottom')
    {
        $unsorted = [];
        $srcList = [];
        $assetList = [];
        foreach ($this->javascripts as $id => $scriptDefinition) {
            if ($scriptDefinition['location'] == $location) {
                $unsorted[$id] = $scriptDefinition;
            }
        }

        $queue = $this->getResolver()->register($unsorted)->resolveAll();
        foreach ($queue as $def) {
            $srcList[] = $def['src'];
        }
        return $srcList;
    }

    public function getStyles()
    {
        $srcList = [];
        $queue = $this->getResolver()->register($this->stylesheets)->resolveAll();
        foreach ($queue as $def) {
            $srcList[] = $def['src'];
        }
        return $srcList;
    }

    /**
     * @return DependencyResolverInterface
     */
    protected function getResolver()
    {
        $class = $this->resolverClass;
        return new $class;
    }

    /**
     * @return FileLocator
     */
    protected function getLocator()
    {
        return $this->container->get('file_locator');
    }
}
