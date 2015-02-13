<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

use Symfony\Component\Routing\RouterInterface;

/**
 * Class Toolbar
 * @package Chamilo\CoreBundle\Component\Editor
 */
class Toolbar
{
    public $config = array();
    public $urlGenerator;

    /**
     * @param Router $urlGenerator
     * @param string $toolbar
     * @param array $config
     * @param string $prefix
     */
    public function __construct(
        RouterInterface $urlGenerator,
        $toolbar = null,
        $config = array(),
        $prefix = null
    ) {
        if (!empty($toolbar)) {
            $class = __NAMESPACE__."\\".$prefix."\\Toolbar\\".$toolbar;
            if (class_exists($class)) {
                $toolbarObj = new $class($urlGenerator);
                $this->setConfig($toolbarObj->getConfig());
            }
        }

        if (!empty($config)) {
            $this->updateConfig($config);
        }
        $this->urlGenerator = $urlGenerator;
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
