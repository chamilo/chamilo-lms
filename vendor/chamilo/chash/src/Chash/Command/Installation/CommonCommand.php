<?php

namespace Chash\Command\Installation;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Alchemy\Zippy\Zippy;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class CommonCommand
 * @package Chash\Command\Installation
 */
class CommonCommand extends AbstractCommand
{
    public $portalSettings;
    public $databaseSettings;
    public $adminSettings;
    public $rootSys;
    public $configurationPath = null;
    public $configuration = array();
    public $extraDatabaseSettings;
    private $migrationConfigurationFile;

    /**
    * @param array $configuration
    */
    public function setConfigurationArray(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
    * @return array
    */
    public function getConfigurationArray()
    {
        return $this->configuration;
    }

    /**
     * @param string $path
     */
    public function setConfigurationPath($path)
    {
        $this->configurationPath = $path;
    }

    /**
     * @return string
     */
    public function getConfigurationPath()
    {
        return $this->configurationPath;
    }

    /**
     * @param array $portalSettings
     */
    public function setPortalSettings(array $portalSettings)
    {
        $this->portalSettings = $portalSettings;
    }

    /**
     * @return array
     */
    public function getPortalSettings()
    {
        return $this->portalSettings;
    }

    /**
     * @param array $databaseSettings
     */
    public function setDatabaseSettings(array $databaseSettings)
    {
        $user = isset($databaseSettings['dbuser']) ? $databaseSettings['dbuser'] : $databaseSettings['user'];
        $password = isset($databaseSettings['dbpassword']) ? $databaseSettings['dbpassword'] : $databaseSettings['password'];

        // Try db_port
        $dbPort = isset($databaseSettings['db_port']) ? $databaseSettings['db_port'] : null;

        // Try port
        if (empty($dbPort)) {
            $dbPort = isset($databaseSettings['port']) ? $databaseSettings['port'] : null;
        }

        $hostParts = explode(':', $databaseSettings['host']);
        if (isset($hostParts[1]) && !empty($hostParts[1])) {
            $dbPort = $hostParts[1];
            $databaseSettings['host'] = str_replace(':'.$dbPort, '', $databaseSettings['host']);
        }
        $this->databaseSettings = $databaseSettings;

        if (!empty($dbPort)) {
            $this->databaseSettings['port'] = $dbPort;
        }
        $this->databaseSettings['user'] = $user;
        $this->databaseSettings['password'] = $password;
    }

    /**
     * @return array
     */
    public function getDatabaseSettings()
    {
        return $this->databaseSettings;
    }

    /**
    * @param array $databaseSettings
    */
    public function setExtraDatabaseSettings(array $databaseSettings)
    {
        $this->extraDatabaseSettings = $databaseSettings;
    }

    /**
     * @return mixed
     */
    public function getExtraDatabaseSettings()
    {
        return $this->extraDatabaseSettings;
    }

    /**
     * @param array $adminSettings
     */
    public function setAdminSettings(array $adminSettings)
    {
        $this->adminSettings = $adminSettings;
    }

    /**
     * @return array
     */
    public function getAdminSettings()
    {
        return $this->adminSettings;
    }

    /**
     * @param string $path
     */
    public function setRootSys($path)
    {
        $this->rootSys = $path;
    }

    /**
     * @return string
     */
    public function getRootSys()
    {
        return $this->rootSys;
    }

    /**
     * @return string
     */
    public function getCourseSysPath()
    {
        if (is_dir($this->getRootSys().'courses')) {
            return $this->getRootSys().'courses';
        }

        if (is_dir($this->getRootSys().'data/courses')) {
            return $this->getRootSys().'data/courses';
        }

        return null;
    }

    /**
     * @return string
     */
    public function getInstallationFolder()
    {

        $chashFolder = dirname(dirname(dirname(__FILE__)));
        return $chashFolder.'/Resources/Database/';
    }

    /**
     * Gets the installation version path
     *
     * @param string $version
     *
     * @return string
     */
    public function getInstallationPath($version)
    {
        if ($version == 'master') {
            $version = $this->getLatestVersion();
        }
        return $this->getInstallationFolder().$version.'/';
    }

    /**
     * Gets the version name folders located in main/install
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $installPath = $this->getInstallationFolder();
        $dir = new \DirectoryIterator($installPath);
        $dirList = array();
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $dirList[$fileInfo->getFilename()] = $fileInfo->getFilename();
            }
        }
        natsort($dirList);
        return $dirList;
    }

    /**
     * @return array
     */
    public function getAdminSettingsParams()
    {
        return array(
            'firstname' => array(
                'attributes' => array(
                    'label' => 'Firstname',
                    'data' =>  'John'
                ),
                'type' => 'text'
            ),
            'lastname' =>  array(
                'attributes' => array(
                    'label' => 'Lastname',
                    'data' =>  'Doe'
                ),
                'type' => 'text'
            ),
            'username' => array(
                'attributes' => array(
                    'label' => 'Username',
                    'data' =>  'admin'
                ),
                'type' => 'text'
            ),
            'password' => array(
                'attributes' => array(
                    'label' => 'Password',
                    'data' =>  'admin'
                ),
                'type' => 'password'
            ),
            'email' => array(
                'attributes' => array(
                    'label' => 'Email',
                    'data' =>  'admin@example.org'
                ),
                'type' => 'email'
            ),
            'language' => array(
                'attributes' => array(
                    'label' => 'Language',
                    'data' =>  'english'
                ),
                'type' => 'text'
            ),
            'phone' => array(
                'attributes' => array(
                    'label' => 'Phone',
                    'data' =>  '123456'
                ),
                'type' => 'text'
            )
        );
    }

    /**
     * @return array
     */
    public function getPortalSettingsParams()
    {
        return array(
            'sitename' => array(
                'attributes' => array(
                    'label' => 'Site name',
                    'data' => 'Campus Chamilo',
                ),
                'type' => 'text'
            ),
            'institution' => array(
                'attributes' => array(
                    'data' => 'Chamilo',
                ),
                'type' => 'text'
            ),
            'institution_url' => array(
                'attributes' => array(
                    'label' => 'URL',
                    'data' => 'http://localhost/',
                ),
                'type' => 'text'
            ),
            'encrypt_method' => array(
                'attributes' => array(
                    'choices' => array(
                        'sha1' => 'sha1',
                        'md5' => 'md5',
                        'none' => 'none'
                    ),
                    'data' => 'sha1'
                ),

                'type' => 'choice'
            ),
            'permissions_for_new_directories' => array(
                'attributes' => array(
                    'data' => '0777',
                ),
                'type' => 'text'
            ),
            'permissions_for_new_files' => array(
                'attributes' => array(
                    'data' => '0666',
                ),
                'type' => 'text'
            ),
        );
    }

    /**
     * Database parameters that are going to be parsed during the console/browser installation
     * @return array
     */
    public function getDatabaseSettingsParams()
    {
        return array(
            'driver' => array(
                'attributes' => array(
                    'choices' =>
                        array(
                            'pdo_mysql' => 'pdo_mysql',
                            'pdo_sqlite' => 'pdo_sqlite',
                            'pdo_pgsql' => 'pdo_pgsql',
                            'pdo_oci' => 'pdo_oci',
                            'ibm_db2' => 'ibm_db2',
                            'pdo_ibm' => 'pdo_ibm',
                            'pdo_sqlsrv' => 'pdo_sqlsrv'
                        ),
                    'data' => 'pdo_mysql'
                ),
                'type' => 'choice'
            ),
            'host' => array(
                'attributes' => array(
                    'label' => 'Host',
                    'data' => 'localhost',
                ),
                'type' => 'text'
            ),
            'port' => array(
                'attributes' => array(
                    'label' => 'Port',
                    'data' => '3306',
                ),
                'type' => 'text'
            ),
            'dbname' => array(
                'attributes' => array(
                    'label' => 'Database name',
                    'data' => 'chamilo',
                ),
                'type' => 'text'
            ),
            'dbuser' => array(
                'attributes' => array(
                    'label' => 'User',
                    'data' => 'root',
                ),
                'type' => 'text'
            ),
            'dbpassword' => array(
                'attributes' => array(
                    'label' => 'Password',
                    'data' => 'root',
                ),
                'type' => 'password'
            )
        );
    }

    /**
     * @return string
     */
    public function getLatestVersion()
    {
        return '1.10.0';
    }

    /**
     * Gets the content of a version from the available versions
     *
     * @param string $version
     *
     * @return bool
     */
    public function getAvailableVersionInfo($version)
    {
        $versionList = $this->availableVersions();
        foreach ($versionList as $versionName => $versionInfo) {
            if ($version == $versionName) {
                return $versionInfo;
            }
        }

        return false;
    }

    /**
     * Gets the min version available to migrate with this command
     * @return mixed
     */
    public function getMinVersionSupportedByInstall()
    {
        return key($this->availableVersions());
    }

    /**
     * Gets an array with the supported versions to migrate
     * @return array
     */
    public function getVersionNumberList()
    {
        $versionList = $this->availableVersions();
        $versionNumberList = array();
        foreach ($versionList as $version => $info) {
            $versionNumberList[] = $version;
        }

        return $versionNumberList;
    }

    /**
     * Gets an array with the settings for every supported version
     *
     * @return array
     */
    public function availableVersions()
    {
        $versionList = array(
            '1.8.7' => array(
                'require_update' => false,
            ),
            '1.8.8' => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.8.7-1.8.8-pre.sql',
                'post' => null,
                'update_db' => 'update-db-1.8.7-1.8.8.inc.php',
                //'update_files' => 'update-files-1.8.7-1.8.8.inc.php',
                'hook_to_doctrine_version' => '8' //see ChamiloLMS\Migrations\Version8.php file
            ),
            '1.8.8.2' => array(
                'require_update' => false,
                'parent' => '1.8.8'
            ),
            '1.8.8.4' => array(
                'require_update' => false,
                'parent' => '1.8.8'
            ),
            '1.8.8.6' => array(
                'require_update' => false,
                'parent' => '1.8.8'
            ),
            '1.9.0' => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.8.8-1.9.0-pre.sql',
                'post' => null,
                'update_db' => 'update-db-1.8.8-1.9.0.inc.php',
                'update_files' => 'update-files-1.8.8-1.9.0.inc.php',
                'hook_to_doctrine_version' => '9'
            ),
            '1.9.2' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.9.4' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.9.6' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.9.8' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.10.0'  => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.9.0-1.10.0-pre.sql',
                'post' => 'migrate-db-1.9.0-1.10.0-post.sql',
                'update_db' => 'update-db-1.9.0-1.10.0.inc.php',
                'update_files' => null,
                'hook_to_doctrine_version' => '10'
            ),
            '1.11.0'  => array(
                'require_update' => true,
                /*'pre' => 'pre.sql',
                'post' => 'post.sql',
                'update_db' => 'update.php',*/
                'update_files' => null,
                'hook_to_doctrine_version' => '11'
            ),
            'master'  => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.9.0-1.10.0-pre.sql',
                'post' => 'migrate-db-1.9.0-1.10.0-post.sql',
                'update_db' => 'update-db-1.9.0-1.10.0.inc.php',
                'update_files' => null,
                'hook_to_doctrine_version' => '10'
            )
        );

        return $versionList;
    }

    /**
     * Gets the Doctrine configuration file path
     * @return string
     */
    public function getMigrationConfigurationFile()
    {
        return $this->migrationConfigurationFile;
    }

    /**
     * @param string $file
     */
    public function setMigrationConfigurationFile($file)
    {
        $this->migrationConfigurationFile = $file;
    }

    /**
     *
     * @return \Chash\Helpers\ConfigurationHelper
     */
    public function getConfigurationHelper()
    {
        return $this->getHelper('configuration');
    }

    /**
     * @todo move to configurationhelper
     * @param string $path
     */
    public function setRootSysDependingConfigurationPath($path)
    {
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($path);

        if ($configurationPath == false) {
            //  Seems an old installation!
            $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);
            $this->setRootSys(realpath($configurationPath.'/../../../').'/');
        } else {
            // Chamilo installations > 1.10
            $this->setRootSys(realpath($configurationPath.'/../').'/');
        }
    }

    /**
     * Writes the configuration file for the first time (install command)
     * @param string $version
     * @param string $path
     * @return bool
     *
     */
    public function writeConfiguration($version, $path)
    {
        $portalSettings = $this->getPortalSettings();
        $databaseSettings = $this->getDatabaseSettings();
        $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);

        // Creates a YML File

        $configuration = array();

        $configuration['db_host'] = $databaseSettings['host'];
        $configuration['db_port'] = $databaseSettings['port'];
        $configuration['db_user'] = $databaseSettings['dbuser'];
        $configuration['db_password'] = $databaseSettings['dbpassword'];
        $configuration['main_database'] = $databaseSettings['dbname'];
        $configuration['driver'] = $databaseSettings['driver'];

        $configuration['root_web'] = $portalSettings['institution_url'];
        $configuration['root_sys'] = $this->getRootSys();
        $configuration['security_key'] = md5(uniqid(rand().time()));

        // Hash function method
        $configuration['password_encryption']      = $portalSettings['encrypt_method'];
        // Session lifetime
        $configuration['session_lifetime']         = 3600;
        // Activation for multi-url access
        $configuration['multiple_access_urls']   = false;
        //Deny the elimination of users
        $configuration['deny_delete_users']        = false;
        //Prevent all admins from using the "login_as" feature
        $configuration['login_as_forbidden_globally'] = false;

        // Version settings
        $configuration['system_version']           = $version;

        if (file_exists($this->getRootSys().'config/configuration.dist.php')) {
            $contents = file_get_contents($this->getRootSys().'config/configuration.dist.php');
        } else {
            // Try the old one
            //$contents = file_get_contents($this->getRootSys().'main/inc/conf/configuration.dist.php');
            $contents = file_get_contents($this->getRootSys().'main/install/configuration.dist.php');
        }

        $config['{DATE_GENERATED}'] = date('r');
        $config['{DATABASE_HOST}'] = $configuration['db_host'];
        $config['{DATABASE_PORT}'] = $configuration['db_port'];
        $config['{DATABASE_USER}'] = $configuration['db_user'];
        $config['{DATABASE_PASSWORD}'] = $configuration['db_password'];
        $config['{DATABASE_MAIN}'] = $configuration['main_database'];
        $config['{DATABASE_DRIVER}'] = $configuration['driver'];

        $config['{COURSE_TABLE_PREFIX}'] = '';
        $config['{DATABASE_GLUE}'] = "`.`"; // keeping for backward compatibility
        $config['{DATABASE_PREFIX}'] = '';
        $config['{DATABASE_STATS}'] = $configuration['main_database'];
        $config['{DATABASE_SCORM}'] = $configuration['main_database'];
        $config['{DATABASE_PERSONAL}'] = $configuration['main_database'];
        $config['TRACKING_ENABLED'] = "'true'";
        $config['SINGLE_DATABASE'] = "false";

        $config['{ROOT_WEB}'] = $portalSettings['institution_url'];
        $config['{ROOT_SYS}'] = $this->getRootSys();

        $config['{URL_APPEND_PATH}'] = "";
        $config['{SECURITY_KEY}'] = $configuration['security_key'];
        $config['{ENCRYPT_PASSWORD}'] = $configuration['password_encryption'];

        $config['SESSION_LIFETIME'] = 3600;
        $config['{NEW_VERSION}'] = $version;
        $config['NEW_VERSION_STABLE'] = 'true';

        foreach ($config as $key => $value) {
            $contents = str_replace($key, $value, $contents);
        }
        $newConfigurationFile = $configurationPath.'configuration.php';

        $result = file_put_contents($newConfigurationFile, $contents);

        return $result;
    }

    /**
     * Updates the configuration.yml file
     *
     * @param OutputInterface $output
     * @param bool $dryRun
     * @param array $newValues
     * @return bool
     */
    public function updateConfiguration(OutputInterface $output, $dryRun, $newValues)
    {
        $this->getConfigurationPath();

        $_configuration = $this->getConfigurationArray();

        // Merging changes
        if (!empty($newValues)) {
            $_configuration = array_merge($_configuration, $newValues);
        }

        $paramsToRemove = array(
            'tracking_enabled',
            //'single_database', // still needed fro version 1.9.8
            //'table_prefix',
            //'db_glue',
            'db_prefix',
            //'url_append',
            'statistics_database',
            'user_personal_database',
            'scorm_database'
        );

        foreach ($_configuration as $key => $value) {
            if (in_array($key, $paramsToRemove)) {
                unset($_configuration[$key]);
            }
        }

        // See http://zf2.readthedocs.org/en/latest/modules/zend.config.introduction.html
        $config = new \Zend\Config\Config($_configuration, true);
        $writer = new \Zend\Config\Writer\PhpArray();
        $content = $writer->toString($config);

        $content = str_replace('return', '$_configuration = ', $content);
        $configurationPath = $this->getConfigurationPath();
        $newConfigurationFile = $configurationPath.'configuration.php';

        if ($dryRun == false) {
            file_put_contents($newConfigurationFile, $content);
            $output->writeln("<comment>File updated: $newConfigurationFile</comment>");
        } else {
            $output->writeln("<comment>File to be updated (dry-run is on): $newConfigurationFile</comment>");
            $output->writeln($content);
        }
        return file_exists($newConfigurationFile);
    }

    /**
     * Gets the SQL files relation with versions
     * @return array
     */
    public function getDatabaseMap()
    {

        $defaultCourseData = array(
            array(
                'name' => 'course1',
                'sql' => array(
                    'db_course1.sql',
                ),
            ),
            array(
                'name' => 'course2',
                'sql' => array(
                    'db_course2.sql'
                )
            ),
        );

        return array(
            '1.8.7' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql'
                            ),
                        ),
                    ),
                    'course' => $defaultCourseData
                ),
            ),
            '1.8.8' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql'
                            ),
                        ),
                    ),
                    'course' => $defaultCourseData
                ),
            ),
            '1.9.0' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_course.sql',
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql'
                            ),
                        ),
                    ),
                )
            ),
            '1.10.0' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_course.sql',
                                'db_main.sql'
                            ),
                        ),
                    ),
                )
            ),
            'master' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_course.sql',
                                'db_main.sql'
                            ),
                        ),
                    ),
                )
            )
        );
    }

    /**
     * Set Doctrine settings
     */
    protected function setDoctrineSettings()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $reader = new AnnotationReader();

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array());
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        $em = \Doctrine\ORM\EntityManager::create($this->getDatabaseSettings(), $config);

        // Fixes some errors
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $platform->registerDoctrineTypeMapping('set', 'string');

        $helpers = array(
            'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
            'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
            'configuration' => new \Chash\Helpers\ConfigurationHelper()
        );

        foreach ($helpers as $name => $helper) {
            $this->getApplication()->getHelperSet()->set($helper, $name);
        }
    }

    /**
     * @param string $version
     * @param string $path
     * @param array $databaseList
     */
    protected function setConnections($version, $path, $databaseList)
    {
        $_configuration = $this->getHelper('configuration')->getConfiguration($path);

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $reader = new AnnotationReader();

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array());
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        foreach ($databaseList as $section => &$dbList) {
            foreach ($dbList as &$dbInfo) {
                $params = $this->getDatabaseSettings();

                if (isset($_configuration['single_database']) && $_configuration['single_database'] == true) {
                    $em = \Doctrine\ORM\EntityManager::create($params, $config);
                } else {
                    if ($section == 'course') {
                        if (version_compare($version, '1.10.0', '<=')) {
                            if (strpos($dbInfo['database'], '_chamilo_course_') === false) {
                                //$params['dbname'] = $params['dbname'];
                            } else {
                                $params['dbname'] = str_replace('_chamilo_course_', '', $dbInfo['database']);
                            }
                        }
                        $em = \Doctrine\ORM\EntityManager::create($params, $config);
                    } else {
                        $em = \Doctrine\ORM\EntityManager::create($params, $config);
                    }
                }
                $helper = new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection());
                $this->getApplication()->getHelperSet()->set($helper, $dbInfo['database']);
            }
        }
    }

    /**
     * @param \Symfony\Component\Finder\Finder $files
     * @param OutputInterface $output
     * @return int
     */
    public function removeFiles(\Symfony\Component\Finder\Finder $files, OutputInterface $output)
    {
        $dryRun = $this->getConfigurationHelper()->getDryRun();

        if (empty($files)) {
            $output->writeln('<comment>No files found.</comment>');
            return 0;
        }

        $fs = new Filesystem();
        try {
            if ($dryRun) {
                $output->writeln('<comment>Files to be removed (--dry-run is on).</comment>');
                foreach ($files as $file) {
                    $output->writeln($file->getPathName());
                }
            } else {
                $output->writeln('<comment>Removing files:</comment>');
                foreach ($files as $file) {
                    $output->writeln($file->getPathName());
                }
                $fs->remove($files);
            }

        } catch (IOException $e) {
            echo "\n An error occurred while removing the directory: ".$e->getMessage()."\n ";
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param array $params
     * @return array
     */
    public function getParamsFromOptions(InputInterface $input, array $params)
    {
        $filledParams = array();

        foreach ($params as $key => $value) {
            $newValue = $input->getOption($key);
            $filledParams[$key] = $newValue;
        }

        return $filledParams;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $version
     * @param string $updateInstallation
     * @param string $defaultTempFolder
     * @return int|null|String
     */
    public function getPackage(OutputInterface $output, $version, $updateInstallation, $defaultTempFolder)
    {
        $fs = new Filesystem();

        // Download the chamilo package from from github:
        if (empty($updateInstallation)) {
            $versionTag = str_replace('.', '_', $version);
            $updateInstallation = "https://github.com/chamilo/chamilo-lms/archive/CHAMILO_".$versionTag."_STABLE.zip";

            if ($version == 'master') {
                $updateInstallation = "https://github.com/chamilo/chamilo-lms/archive/master.zip";
            }
        }

        $updateInstallationOriginal = $updateInstallation;

        if (!empty($updateInstallation)) {

            // Check temp folder
            if (!is_writable($defaultTempFolder)) {
                $output->writeln("<comment>We don't have permissions to write in the temp folder: $defaultTempFolder</comment>");
                return 0;
            }

            // Download file?
            if (strpos($updateInstallation, 'http') === false) {
                if (!file_exists($updateInstallation)) {
                    $output->writeln("<comment>File does not exists: $updateInstallation</comment>");
                    return 0;
                }
            } else {
                $urlInfo = parse_url($updateInstallation);

                $updateInstallationLocalName = $defaultTempFolder.'/'.basename($urlInfo['path']);
                if (!file_exists($updateInstallationLocalName)) {

                    $output->writeln("<comment>Executing</comment> <info>wget -O $updateInstallationLocalName '$updateInstallation'</info>");
                    $output->writeln('');

                    $execute = "wget -O ".$updateInstallationLocalName." '$updateInstallation'\n";

                    $systemOutput = shell_exec($execute);

                    $systemOutput = str_replace("\n", "\n\t", $systemOutput);
                    $output->writeln($systemOutput);
                } else {
                    $output->writeln("<comment>Seems that the chamilo v".$version." has been already downloaded. File location:</comment> <info>$updateInstallationLocalName</info>");
                }

                $updateInstallation = $updateInstallationLocalName;

                if (!file_exists($updateInstallationLocalName)) {
                    $output->writeln("<error>Can't download the file!</error>");
                    $output->writeln("<comment>Check if you can download this file in your browser first:</comment> <info>$updateInstallation</info>");
                    return 0;
                }
            }

            if (file_exists($updateInstallation)) {
                $zippy = Zippy::load();
                $archive = $zippy->open($updateInstallation);

                $folderPath = $defaultTempFolder.'/chamilo-v'.$version.'-'.date('y-m-d');

                if (!is_dir($folderPath)) {
                    $fs->mkdir($folderPath);
                } else {
                    // Load from cache
                    $chamiloPath = $folderPath.'/chamilo-lms-CHAMILO_'.$versionTag.'_STABLE/main/inc/global.inc.php';
                    if (file_exists($chamiloPath)) {
                        $output->writeln("<comment>Files have been already extracted here: </comment><info>".$folderPath.'/chamilo-lms-CHAMILO_'.$versionTag.'_STABLE/'."</info>");
                        return $folderPath.'/chamilo-lms-CHAMILO_'.$versionTag.'_STABLE/';
                    }
                }

                $location = null;

                if (is_dir($folderPath)) {
                    $output->writeln("<comment>Extracting files here:</comment> <info>$folderPath</info>");

                    try {
                        $archive->extract($folderPath);
                        /** @var \Alchemy\Zippy\Archive\Member $member */
                        foreach ($archive as $member) {
                            if ($member->isDir()) {
                                $location = $member->getLocation();
                                $globalFile = $folderPath.'/'.$location.'main/inc/global.inc.php';
                                if (file_exists($globalFile) && is_file($globalFile)) {
                                    $location = realpath($folderPath.'/'.$location).'/';
                                    $output->writeln('<comment>Chamilo file detected:</comment> <info>'.$location.'main/inc/lib/global.inc.php</info>');
                                    break;
                                }
                            }
                        }
                    } catch (\Alchemy\Zippy\Exception\RunTimeException $e) {
                        $output->writeln("<comment>It seems that this file doesn't contain a Chamilo package:</comment> <info>$updateInstallationOriginal</info>");

                        unlink($updateInstallation);
                        $output->writeln("<comment>Removing file</comment>:<info>$updateInstallation</info>");

                        //$output->writeln("Error:");
                        //$output->writeln($e->getMessage());
                        return 0;
                    }
                }

                $chamiloLocationPath = $location;

                if (empty($chamiloLocationPath)) {
                    $output->writeln("<error>Chamilo folder structure not found in package.</error>");
                    return 0;
                }

                return $chamiloLocationPath;
            } else {
                $output->writeln("<comment>File doesn't exist.</comment>");
                return 0;
            }
        }
    }

    /**
     * @param array $_configuration
     * @param string $courseDatabase
     * @return null|string
     */
    public function getTablePrefix($_configuration, $courseDatabase = null)
    {
        $singleDatabase = isset($_configuration['single_database']) ? $_configuration['single_database'] : false;
        $tablePrefix = isset($_configuration['table_prefix']) ? $_configuration['table_prefix'] : null;
        //$db_prefix = isset($_configuration['db_prefix']) ? $_configuration['db_prefix'] : null;

        if ($singleDatabase) {
            // the $courseDatabase already contains the $db_prefix;
            $prefix = $tablePrefix.$courseDatabase.'_';
        } else {
            $prefix = $tablePrefix;
        }

        return $prefix;
    }

    /**
     * @param OutputInterface $output
     * @param string $chamiloLocationPath
     * @param string $destinationPath
     * @return int
     */
    public function copyPackageIntoSystem(OutputInterface $output, $chamiloLocationPath, $destinationPath)
    {
        $fileSystem = new Filesystem();

        if (empty($destinationPath)) {
            $destinationPath = $this->getRootSys();
        }

        if (empty($chamiloLocationPath)) {
            $output->writeln("<error>The chamiloLocationPath variable is empty<error>");
            return 0;
        }

        $output->writeln("<comment>Copying files from </comment><info>$chamiloLocationPath</info><comment> to </comment><info>".$destinationPath."</info>");

        if (empty($destinationPath)) {
            $output->writeln("<error>The root path was not set.<error>");
            return 0;
        } else {
            $fileSystem->mirror($chamiloLocationPath, $destinationPath, null, array('override' => true));
            $output->writeln("<comment>Copy finished.<comment>");
            return 1;
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $title
     */
    public function writeCommandHeader(OutputInterface $output, $title)
    {
        $output->writeln('<comment>-----------------------------------------------</comment>');
        $output->writeln('<comment>'.$title.'</comment>');
        $output->writeln('<comment>-----------------------------------------------</comment>');
    }

    /**
     * Returns the config file list
     * @return array
     */
    public function getConfigFiles()
    {
        return array(
            'portfolio.conf.dist.php',
            'events.conf.dist.php',
            'add_course.conf.dist.php',
            'mail.conf.dist.php',
            'auth.conf.dist.php',
            'profile.conf.dist.php',
            'course_info.conf.php'
        );
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function generateConfFiles(OutputInterface $output)
    {
        $confDir = $this->getConfigurationPath();
        $fs = new Filesystem();

        $configList = $this->getConfigFiles();
        foreach ($configList as $file) {
            if (file_exists($confDir.$file)) {
                $newConfFile = $confDir.str_replace('dist.', '', $file);
                if (!file_exists($newConfFile)) {
                    $fs->copy($confDir.$file, $newConfFile);
                    $output->writeln("<comment>File generated:</comment> <info>$newConfFile</info>");
                }
            }
        }
    }

    /**
     * Copy files from main/inc/conf to the new location config
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function copyConfigFilesToNewLocation(OutputInterface $output)
    {
        $output->writeln('<comment>Copy files to new location</comment>');
        // old config main/inc/conf
        $confDir = $this->getConfigurationPath();

        $configurationPath = $this->getConfigurationHelper()->convertOldConfigurationPathToNewPath($confDir);

        $fs = new Filesystem();
        $configList = $this->getConfigFiles();
        $configList[] = 'configuration.dist.php';
        foreach ($configList as $file) {
            // This file contains a get_lang that cause a fatal error.
            if (in_array($file, array('events.conf.dist.php', 'mail.conf.dist.php'))) {
                continue;
            }
            $configFile = str_replace('dist.', '', $file);

            if (file_exists($confDir.$configFile)) {
                $output->writeln("<comment> Moving file from: </comment>".$confDir.$configFile);
                $output->writeln("<comment> to: </comment>".$configurationPath.$configFile);
                if (!file_exists($configurationPath.$configFile)) {
                    $fs->copy($confDir.$configFile, $configurationPath.$configFile);
                }
            } else {
                $output->writeln("<comment> File not found: </comment>".$confDir.$configFile);
            }
        }

        $backupConfPath = str_replace('inc/conf', 'inc/conf_old', $confDir);
        if ($confDir != $backupConfPath) {
            $output->writeln('<comment>Renaming conf folder: </comment>'.$confDir.' to '.$backupConfPath.'');
            $fs->rename($confDir, $backupConfPath);
        } else {
            $output->writeln('<comment>No need to rename the conf folder: </comment>'.$confDir.' = '.$backupConfPath.'');
        }
        $this->setConfigurationPath($configurationPath);
    }

    /**
     * @param OutputInterface $output
     * @param $path
     */
    public function removeUnUsedFiles(OutputInterface $output, $path)
    {
        $output->writeln('<comment>Removing unused files</comment>');
        $fs = new Filesystem();

        $list = array(
            'archive',
            'config/course_info.conf.php'
        );

        foreach ($list as $file) {
            $filePath = $path.'/'.$file;
            if ($fs->exists($filePath)) {
                $output->writeln('<comment>Removing: </comment>'.$filePath);
                $fs->remove($filePath);
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function setPortalSettingsInChamilo(OutputInterface $output, \Doctrine\DBAL\Connection $connection)
    {
        $adminSettings = $this->getAdminSettings();

        $connection->update('settings_current', array('selected_value' => $adminSettings['email']), array('variable' => 'emailAdministrator'));
        $connection->update('settings_current', array('selected_value' => $adminSettings['lastname']), array('variable' => 'administratorSurname'));
        $connection->update('settings_current', array('selected_value' => $adminSettings['firstname']), array('variable' => 'administratorName'));
        $connection->update('settings_current', array('selected_value' => $adminSettings['language']), array('variable' => 'platformLanguage'));

        $settings = $this->getPortalSettings();

        $connection->update('settings_current', array('selected_value' => 1), array('variable' => 'allow_registration'));
        $connection->update('settings_current', array('selected_value' => 1), array('variable' => 'allow_registration_as_teacher'));

        $connection->update('settings_current', array('selected_value' => $settings['permissions_for_new_directories']), array('variable' => 'permissions_for_new_directories'));
        $connection->update('settings_current', array('selected_value' => $settings['permissions_for_new_files']), array('variable' => 'permissions_for_new_files'));
        $connection->update('settings_current', array('selected_value' => $settings['institution']), array('variable' => 'Institution'));
        $connection->update('settings_current', array('selected_value' => $settings['institution_url']), array('variable' => 'InstitutionUrl'));
        $connection->update('settings_current', array('selected_value' => $settings['sitename']), array('variable' => 'siteName'));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function setAdminSettingsInChamilo(OutputInterface $output, \Doctrine\DBAL\Connection $connection)
    {
        $settings = $this->getAdminSettings();

        $settings['password'] = $this->getEncryptedPassword($settings['password']);

        $connection->update('user', array('auth_source' => 'platform'), array('user_id' => '1'));
        $connection->update('user', array('username' => $settings['username']), array('user_id' => '1'));
        $connection->update('user', array('firstname' => $settings['firstname']), array('user_id' => '1'));
        $connection->update('user', array('lastname' => $settings['lastname']), array('user_id' => '1'));
        $connection->update('user', array('phone' => $settings['phone']), array('user_id' => '1'));
        $connection->update('user', array('password' => $settings['password']), array('user_id' => '1'));
        $connection->update('user', array('email' => $settings['email']), array('user_id' => '1'));
        $connection->update('user', array('language' => $settings['language']), array('user_id' => '1'));

        // Already updated by the script
        //$connection->insert('admin', array('user_id' => 1));
        $connection->update('user', array('language' => $settings['language']), array('user_id' => '2'));
    }

    /**
     * Generates password.
     *
     * @param string $password
     * @param string $salt
     * @return string
     */
    public function getEncryptedPassword($password, $salt = null)
    {
        $configuration = $this->getConfigurationArray();
        $encryptionMethod = $configuration['password_encryption'];

        switch ($encryptionMethod) {
            case 'sha1':
                return empty($salt) ? sha1($password) : sha1($password.$salt);
            case 'none':
                return $password;
            case 'md5':
            default:
                return empty($salt) ? md5($password) : md5($password.$salt);
        }
    }
}
