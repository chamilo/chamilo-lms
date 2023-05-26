<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

use Symfony\Component\Routing\RouterInterface;

class Toolbar
{
    public array $config = [];
    public RouterInterface $urlGenerator;
    public array $plugins = [];
    public array $defaultPlugins = [];

    /**
     * Toolbar constructor.
     *
     * @param RouterInterface $urlGenerator
     * @param null            $toolbar
     * @param array           $config
     * @param null            $prefix
     */
    public function __construct(
        $urlGenerator,
        $toolbar = null,
        $config = [],
        $prefix = null
    ) {
        $this->urlGenerator = $urlGenerator;

        if (!empty($toolbar)) {
            $class = __NAMESPACE__.'\\'.$prefix.'\\Toolbar\\'.$toolbar;
            if (class_exists($class)) {
                $this->setConfig($config);
                $toolbarObj = new $class($urlGenerator, $toolbar, $config);
                $config = $toolbarObj->getConfig();

                if ('true' === api_get_setting('editor.full_ckeditor_toolbar_set')) {
                    $basicClass = __NAMESPACE__.'\\'.$prefix.'\\Toolbar\\Basic';
                    $basicObj = new $basicClass($urlGenerator, $toolbar, $config);
                    $basicConfig = $basicObj->getConfig();
                    if ('true' === api_get_setting('more_buttons_maximized_mode')) {
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
    }

    /**
     * @return RouterInterface
     */
    public function getUrlGenerator()
    {
        return $this->urlGenerator;
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

        return $this->getConfigAttribute('extraPlugins').implode(',', $plugins);
    }

    /**
     * Get plugins by default in all editors in the platform.
     */
    public function getDefaultPlugins(): array
    {
        return $this->defaultPlugins;
    }

    /**
     * Get fixed plugins depending of the toolbar.
     */
    public function getPlugins(): array
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

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function updateConfig(array $config): void
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
    public function setLanguage($language): void
    {
        $this->config['language'] = $language;
    }
}
