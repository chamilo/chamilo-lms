<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

//use Symfony\Component\Routing\RouterInterface;

/**
 * Class Toolbar.
 *
 * @package Chamilo\CoreBundle\Component\Editor
 */
class Toolbar
{
    public $config = [];
    public $urlGenerator;
    public $plugins = [];
    public $defaultPlugins = [];

    /**
     * @param string $toolbar
     * @param array  $config
     * @param string $prefix
     */
    public function __construct(
        $toolbar = null,
        $config = [],
        $prefix = null
    ) {
        if (!empty($toolbar)) {
            $class = __NAMESPACE__."\\".$prefix."\\Toolbar\\".$toolbar;
            if (class_exists($class)) {
                $this->setConfig($config);
                $toolbarObj = new $class();
                $config = $toolbarObj->getConfig();

                if (api_get_configuration_value('full_ckeditor_toolbar_set')) {
                    $basicClass = __NAMESPACE__."\\".$prefix."\\Toolbar\\Basic";
                    $basicObj = new $basicClass();
                    $basicConfig = $basicObj->getConfig();

                    if (api_get_setting('more_buttons_maximized_mode') == 'true') {
                        if (isset($config['toolbar'])) {
                            unset($config['toolbar']);
                        }

                        $config['toolbar_minToolbar'] = $basicConfig['toolbar_minToolbar'];
                        $config['toolbar_maxToolbar'] = $basicConfig['toolbar_maxToolbar'];
                    }

                    $config['height'] = '85px';
                    $config['toolbarCanCollapse'] = true;
                    $config['toolbarStartupExpanded'] = false;
                }

                $this->updateConfig($config);
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
     * Get plugins by default in all editors in the platform.
     *
     * @return array
     */
    public function getDefaultPlugins()
    {
        return $this->defaultPlugins;
    }

    /**
     * Get fixed plugins depending of the toolbar.
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Get dynamic/conditional plugins depending of platform/course settings.
     *
     * @return array
     */
    public function getConditionalPlugins()
    {
        return [];
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

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
