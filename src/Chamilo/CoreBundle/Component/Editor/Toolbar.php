<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

//use Symfony\Component\Routing\RouterInterface;

/**
 * Class Toolbar
 * @package Chamilo\CoreBundle\Component\Editor
 */
class Toolbar
{
    public $config = array();
    public $urlGenerator;
    public $plugins = array();
    public $defaultPlugins = array();

    /**
     * @param string $toolbar
     * @param array  $config
     * @param string $prefix
     */
    public function __construct(
        $toolbar = null,
        $config = array(),
        $prefix = null
    ) {
        if (!empty($toolbar)) {
            $class = __NAMESPACE__."\\".$prefix."\\Toolbar\\".$toolbar;
            if (class_exists($class)) {
                $toolbarObj = new $class();
                $this->setConfig($toolbarObj->getConfig());
            }
        }

        if (!empty($config)) {
            $this->updateConfig($config);
        }
        //$this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string
     */
    public function getPluginsToString()
    {
        $plugins = array_filter(
            array_merge(
                $this->getDefaultPlugins(),
                $this->getPlugins(),
                $this->getConditionalPlugins()
            )
        );

        return
            $this->getConfigAttribute('extraPlugins').
            implode(',', $plugins);
    }

    /**
     * Get plugins by default in all editors in the platform
     * @return array
     */
    public function getDefaultPlugins()
    {
        return $this->defaultPlugins;
    }

    /**
     * Get fixed plugins depending of the toolbar
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Get dynamic/conditional plugins depending of platform/course settings.
     * @return array
     */
    public function getConditionalPlugins()
    {
        return array();
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $config
     */
    public function updateConfig(array $config)
    {
        if (empty($this->config)) {
            $this->setConfig($config);
        } else {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $variable
     *
     * @return array
     */
    public function getConfigAttribute($variable)
    {
        if (isset($this->config[$variable])) {
            return $this->config[$variable];
        }

        return null;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->config['language'] = $language;
    }
}
