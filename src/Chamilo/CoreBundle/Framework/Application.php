<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Framework;

use Zend\Config\Config;

/**
 * Class Application
 * @package Chamilo\CoreBundle\Framework
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

    /**
     * @return Config
     */
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

    /**
     * Reads the configuration.php file
     */
    public function readConfigurationFiles()
    {
        $this->configuration = $this->getConfigurationArrayFromFile();

        if (!empty($this->configuration)) {
            $config = new Config($this->configuration, true);
            $this->installed = true;

            /** Overwriting paths if set */

            $this['path.data'] = empty($config->get('path.data')) ? $this['path.data'] : $config->get('path.data');
            $this['path.course'] = empty($config->get('path.courses')) ? $this['path.courses'] : $config->get('path.courses');
            $this['path.temp'] = empty($config->get('path.temp')) ? $this['path.temp'] : $config->get('path.temp');
            $this['path.logs'] = empty($config->get('path.logs')) ? $this['path.logs'] : $config->get('path.logs');

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

    public function setupDoctrineExtensions()
    {
        if (isset($this->getConfiguration()->main_database) && isset($this['db.event_manager'])) {

            // @todo improvement do not create every time this objects
            $sortableGroup = new \Gedmo\Mapping\Annotation\SortableGroup(array());
            $sortablePosition = new \Gedmo\Mapping\Annotation\SortablePosition(array());
            $tree = new \Gedmo\Mapping\Annotation\Tree(array());
            $tree = new \Gedmo\Mapping\Annotation\TreeParent(array());
            $tree = new \Gedmo\Mapping\Annotation\TreeLeft(array());
            $tree = new \Gedmo\Mapping\Annotation\TreeRight(array());
            $tree = new \Gedmo\Mapping\Annotation\TreeRoot(array());
            $tree = new \Gedmo\Mapping\Annotation\TreeLevel(array());
            $tree = new \Gedmo\Mapping\Annotation\Versioned(array());
            $tree = new \Gedmo\Mapping\Annotation\Loggable(array());
            $tree = new \Gedmo\Loggable\Entity\LogEntry();

            // Setting Doctrine2 extensions
            $timestampableListener = new \Gedmo\Timestampable\TimestampableListener();
            // $app['db.event_manager']->addEventSubscriber($timestampableListener);
            $this['dbs.event_manager']['db_read']->addEventSubscriber($timestampableListener);
            $this['dbs.event_manager']['db_write']->addEventSubscriber($timestampableListener);

            $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
            // $this['db.event_manager']->addEventSubscriber($sluggableListener);
            $this['dbs.event_manager']['db_read']->addEventSubscriber($sluggableListener);
            $this['dbs.event_manager']['db_write']->addEventSubscriber($sluggableListener);

            $sortableListener = new \Gedmo\Sortable\SortableListener();
            // $this['db.event_manager']->addEventSubscriber($sortableListener);
            $this['dbs.event_manager']['db_read']->addEventSubscriber($sortableListener);
            $this['dbs.event_manager']['db_write']->addEventSubscriber($sortableListener);

            $treeListener = new \Gedmo\Tree\TreeListener();
            //$treeListener->setAnnotationReader($cachedAnnotationReader);
            // $this['db.event_manager']->addEventSubscriber($treeListener);
            $this['dbs.event_manager']['db_read']->addEventSubscriber($treeListener);
            $this['dbs.event_manager']['db_write']->addEventSubscriber($treeListener);

            $loggableListener = new \Gedmo\Loggable\LoggableListener();
            if (PHP_SAPI != 'cli') {
                //$userInfo = api_get_user_info();
                if (isset($userInfo) && !empty($userInfo['username'])) {
                    //$loggableListener->setUsername($userInfo['username']);
                }
            }
            $this['dbs.event_manager']['db_read']->addEventSubscriber($loggableListener);
            $this['dbs.event_manager']['db_write']->addEventSubscriber($loggableListener);
        }

    }
}
