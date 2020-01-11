<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Theme;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * Class ThemeManager.
 */
class ThemeManager implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $stylesheets = [];
    protected $javascripts = [];
    protected $locations = [];
    protected $resolverClass;

    /**
     * ThemeManager constructor.
     *
     * @param null $resolverClass
     */
    public function __construct($resolverClass = null)
    {
        $this->resolverClass = $resolverClass ?: 'Chamilo\ThemeBundle\Util\DependencyResolver';
    }

    public function registerScript($id, $src, $deps = [], $location = 'bottom')
    {
        if (!isset($this->javascripts[$id])) {
            $this->javascripts[$id] = [
                'src' => $src,
                'deps' => $deps,
                'location' => $location,
            ];
        }
    }

    /**
     * @param string[] $deps
     */
    public function registerStyle(string $id, string $src, array $deps = [])
    {
        if (!isset($this->stylesheets[$id])) {
            $this->stylesheets[$id] = [
                'src' => $src,
                'deps' => $deps,
            ];
        }
    }

    public function getScripts($location = 'bottom')
    {
        /*$unsorted = [];
        $srcList = [];
        foreach ($this->javascripts as $id => $scriptDefinition) {
            if ($scriptDefinition['location'] == $location) {
                $unsorted[$id] = $scriptDefinition;
            }
        }
        $queue = $this->getResolver()->register($unsorted)->resolveAll();
        foreach ($queue as $def) {
            $srcList[] = $def['src'];
        }

        return $srcList;*/
    }

    public function getStyles()
    {
        /*
        $srcList = [];
        $queue = $this->getResolver()->register($this->stylesheets)->resolveAll();
        foreach ($queue as $def) {
            $srcList[] = $def['src'];
        }

        return $srcList;*/
    }

    protected function getResolver()
    {
        $class = $this->resolverClass;

        return new $class();
    }

    /**
     * @return FileLocator
     */
    protected function getLocator()
    {
        return $this->container->get('file_locator');
    }
}
