<?php

namespace Chash\Command\Installation;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Alchemy\Zippy\Zippy;

class CommonCommand extends AbstractCommand
{
    public $portalSettings;
    public $databaseSettings;
    public $adminSettings;
    public $rootSys;
    public $configurationPath = null;
    public $configuration = array();
    public $extraDatabaseSettings;

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
     * @return null
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
        $this->databaseSettings = $databaseSettings;
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
     * @return array
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
        return realpath(__DIR__.'/../../Resources/Database').'/';
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
            'dbname' => array(
                'attributes' => array(
                    'label' => 'Database name',
                    'data' => 'chamilo',
                ),
                'type' => 'text'
            ),
            'user' => array(
                'attributes' => array(
                    'label' => 'User',
                    'data' => 'root',
                ),
                'type' => 'text'
            ),
            'password' => array(
                'attributes' => array(
                    'label' => 'Password',
                    'data' => 'root',
                ),
                'type' => 'password'
            )
        );
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
        return __DIR__.'/../../Resources/Database/'.$version.'/';
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
        return realpath(__DIR__.'/../../Migrations/migrations.yml');
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
        $configuration['db_user'] = $databaseSettings['user'];
        $configuration['db_password'] = $databaseSettings['password'];
        $configuration['main_database'] = $databaseSettings['dbname'];
        $configuration['driver'] = $databaseSettings['driver'];

        $configuration['root_web'] = $portalSettings['institution_url'];
        $configuration['root_sys'] = $this->getRootSys();

        $configuration['security_key'] = md5(uniqid(rand().time()));

        // Hash function method
        $configuration['password_encryption']      = $portalSettings['encrypt_method'];
        // You may have to restart your web server if you change this
        $configuration['session_stored_in_db']     = false;
        // Session lifetime
        $configuration['session_lifetime']         = 3600;
        // Activation for multi-url access
        $_configuration['multiple_access_urls']   = false;
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
        $config['{DATABASE_USER}'] = $configuration['db_user'];
        $config['{DATABASE_PASSWORD}'] = $configuration['db_password'];
        $config['{DATABASE_MAIN}'] = $configuration['main_database'];
        $config['{DATABASE_DRIVER}'] = $configuration['driver'];

        $config['{COURSE_TABLE_PREFIX}'] = "";
        $config['{DATABASE_GLUE}'] = "";
        $config['{DATABASE_PREFIX}'] = "";
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
     * @param string $version
     *
     * @return bool
     */
    public function updateConfiguration($output, $dryRun, $newValues)
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
                        /*$evm = new \Doctrine\Common\EventManager;
                        $tablePrefix = new \Chash\DoctrineExtensions\TablePrefix($_configuration['table_prefix']);
                        $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $tablePrefix);*/

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
     * @param $files
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    public function removeFiles($files, \Symfony\Component\Console\Output\OutputInterface $output)
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
    public function getParamsFromOptions(\Symfony\Component\Console\Input\InputInterface $input, array $params)
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
    public function getPackage(\Symfony\Component\Console\Output\OutputInterface $output, $version, $updateInstallation, $defaultTempFolder)
    {
        $fs = new Filesystem();
        // Download the chamilo package from from github:
        if (empty($updateInstallation)) {
            $versionTag = str_replace('.', '_', $version);
            $updateInstallation = "https://github.com/chamilo/chamilo-lms/archive/CHAMILO_".$versionTag."_STABLE.zip";
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
                    //$result = file_put_contents($updateInstallationLocalName, file_get_contents($updateInstallation));
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

                                //$output->writeln($folderPath.'/'.$location.'main/inc/lib/global.inc.php');
                                if (file_exists($folderPath.'/'.$location.'global.inc.php')) {
                                    $location = realpath($folderPath.'/'.$location.'../../').'/';
                                    $output->writeln('<comment>Chamilo file detected:</comment> <info>'.$location.'main/inc/lib/global.inc.php</info>');
                                    break;
                                }
                            }
                        }
                    } catch (\Alchemy\Zippy\Exception\RunTimeException $e) {
                        $output->writeln("<comment>It seems that this file doesn't contain a Chamilo package:</comment> <info>$updateInstallationOriginal</info>");
                        $output->writeln("Error:");
                        $output->writeln($e->getMessage());
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
                $output->writeln("<comment>File doesn't exists.</comment>");
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
        $db_prefix = isset($_configuration['db_prefix']) ? $_configuration['db_prefix'] : null;

        if ($singleDatabase) {
            // the $courseDatabase already contains the $db_prefix;
            $prefix = $tablePrefix.$courseDatabase.'_';
        } else {
            $prefix = $tablePrefix;
        }

        return $prefix;
    }

    /**
     * @param $output
     * @param string $chamiloLocationPath
     * @param string $destinationPath
     */
    public function copyPackageIntoSystem($output, $chamiloLocationPath, $destinationPath)
    {
        $fileSystem = new Filesystem();

        if (empty($destinationPath)) {
            $destinationPath = $this->getRootSys();
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
     * @param $output
     * @param string $title
     */
    public function writeCommandHeader($output, $title)
    {
        $output->writeln('<comment>-----------------------------------------------</comment>');
        $output->writeln('<comment>'.$title.'</comment>');
        $output->writeln('<comment>-----------------------------------------------</comment>');
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function generateConfFiles(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $configList = array(
            'portfolio.conf.dist.php',
            'events.conf.dist.php',
            'add_course.conf.dist.php',
            'mail.conf.dist.php',
            'auth.conf.dist.php',
            'profile.conf.dist.php',
        );

        $confDir = $this->getConfigurationPath();

        $fs = new Filesystem();

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
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function setPortalSettingsInChamilo(\Symfony\Component\Console\Output\OutputInterface $output, \Doctrine\DBAL\Connection $connection)
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
    public function setAdminSettingsInChamilo(\Symfony\Component\Console\Output\OutputInterface $output, \Doctrine\DBAL\Connection $connection)
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
                return empty($salt) ? md5($password)  : md5($password.$salt);
        }
    }
}
