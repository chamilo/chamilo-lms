<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Framework;

use Zend\Config;

/**
 * Class Application
 * @package ChamiloLMS\Framework
 */
class Application extends \Silex\Application
{
    public $installed = false;
    public $configurationArray = array();
    public $configuration;

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @param array $paths
     */
    public function bindInstallPaths(array $paths)
    {
        foreach ($paths as $key => $value) {
            $this['path.'.$key] = realpath($value).'/';
        }
    }

    /**
     * @return array
     */
    public function getConfigurationArray()
    {
        return $this->configurationArray;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return bool|mixed
     */
    public function getConfigurationArrayFromFile()
    {
        $configurationFile = $this['path.config'].'configuration.php';
        if (file_exists($configurationFile)) {
            return require_once $configurationFile;
        }

        return array();
    }

    public function readConfigurationFiles()
    {
        $this->configuration = $this->getConfigurationArrayFromFile();

        if (!empty($this->configuration)) {
            $config = new \Zend\Config\Config($this->configuration, true);
            $this->installed = true;

            /** Overwriting paths */

            $this['path.data'] = empty($config->get('path.data')) ? $this['path.data'] : $config->get('path.data');
            $this['path.course'] = empty($config->get('path.courses')) ? $this['path.courses'] : $config->get('path.courses');
            $this['path.temp'] = empty($config->get('path.temp')) ? $this['path.temp'] : $config->get('path.temp');
            $this['path.log'] = empty($config->get('path.log')) ? $this['path.log'] : $config->get('path.log');

            $configPath = $this['path.config'];

            $confFiles = array(
                'auth.conf.php',
                'events.conf.php',
                'mail.conf.php',
                'portfolio.conf.php',
                'profile.conf.php'
            );

            foreach ($confFiles as $confFile) {
                if (file_exists($configPath . $confFile)) {
                    require_once $configPath . $confFile;
                }
            }

            // Fixing $_configuration array

            // Fixes bug in Chamilo 1.8.7.1 array was not set
            /*$administrator['email'] = isset($administrator['email']) ? $administrator['email'] : 'admin@example.com';
            $administrator['name'] = isset($administrator['name']) ? $administrator['name'] : 'Admin';*/

            // Code for transitional purposes, it can be removed right before the 1.8.7 release.
            /*if (empty($_configuration['system_version'])) {
                $_configuration['system_version'] = (!empty($_configuration['dokeos_version']) ? $_configuration['dokeos_version'] : '');
                $_configuration['system_stable'] = (!empty($_configuration['dokeos_stable']) ? $_configuration['dokeos_stable'] : '');
                $_configuration['software_url'] = 'http://www.chamilo.org/';
            }*/

            // For backward compatibility.
            //$this->configuration['dokeos_version'] = isset($this->configuration['system_version']) ? $this->configuration['system_version'] : null;
            //$_configuration['dokeos_stable'] = $_configuration['system_stable'];
            //$userPasswordCrypted = (!empty($_configuration['password_encryption']) ? $_configuration['password_encryption'] : 'sha1');
            //$this->configuration['password_encryption'] = isset($this->configuration['password_encryption']) ? $this->configuration['password_encryption'] : 'sha1';
            $this->configurationArray = $this->configuration;
            $this->configuration = $config;
        }
    }
}
