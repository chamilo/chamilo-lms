<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Component\Editor;

/**
 * Class Toolbar
 * @package ChamiloLMS\Component\Editor
 */
class Toolbar
{
    public $config;

    /**
     * @param string $toolbar
     * @param array $config
     * @param string $prefix
     */
    public function __construct($toolbar = null, $config = array(), $prefix = null)
    {
        if (!empty($toolbar)) {
            $class = __NAMESPACE__."\\".$prefix."\\Toolbar\\".$toolbar;
            if (class_exists($class)) {
                $toolbarObj = new $class;
                $this->setConfig($toolbarObj->getConfig());
            }
        }

        if (!empty($config)) {
            $this->updateConfig($config);
        }
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
     * @param $language
     */
    public function setLanguage($language)
    {
        $this->config['language'] = $language;
    }
}
