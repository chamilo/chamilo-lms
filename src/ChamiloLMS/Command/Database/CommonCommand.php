<?php

namespace ChamiloLMS\Command\Database;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;

class CommonCommand extends AbstractCommand
{
    /**
     * Gets the installation version path
     *
     * @param string $version
     *
     * @return string
     */
    public function getInstallationPath($version)
    {
        return __DIR__.'/../../../../main/install/'.$version.'/';
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
        return api_get_path(SYS_PATH).'src/ChamiloLMS/Migrations/migrations.yml';
    }

    /**
     * Writes the configuration file a yml file
     * @param string $version
     */
    public function writeConfiguration($version)
    {
        $portalSettings = $this->getPortalSettings();
        $databaseSettings = $this->getDatabaseSettings();

        $configurationPath = $this->getHelper('configuration')->getNewConfigurationPath();

        // Creates a YML File

        $configuration = array();
        $configuration['system_version'] = $version;

        $configuration['db_host'] = $databaseSettings['host'];
        $configuration['db_user'] = $databaseSettings['user'];
        $configuration['db_password'] = $databaseSettings['password'];
        $configuration['main_database'] = $databaseSettings['dbname'];
        $configuration['driver'] = $databaseSettings['driver'];

        $configuration['root_web'] = $portalSettings['url'];
        $configuration['root_sys'] = $this->getRootSys();

        $configuration['security_key']      = md5(uniqid(rand().time()));

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
        $configuration['system_version']           = '1.10.0';

        /*
        $dumper = new Dumper();
        $yaml = $dumper->dump($configuration, 2);

        $newConfigurationFile = $configurationPath.'configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);*/

        // Create a configuration.php
        $configurationPath.'configuration.dist.php';

        $contents = file_get_contents($configurationPath.'configuration.dist.php');

        $configuration['{DATE_GENERATED}'] = date('r');
        $config['{DATABASE_HOST}'] = $configuration['db_host'];
        $config['{DATABASE_USER}'] = $configuration['db_user'];
        $config['{DATABASE_PASSWORD}'] = $configuration['db_password'];
        $config['{DATABASE_MAIN}'] = $configuration['main_database'];
        $config['{DATABASE_DRIVER}'] = $configuration['driver'];

        $config['{ROOT_WEB}'] = $portalSettings['url'];
        $config['{ROOT_SYS}'] = $this->getRootSys();

        //$config['{URL_APPEND_PATH}'] = $urlAppendPath;
        $config['{SECURITY_KEY}'] = $configuration['security_key'];
        $config['{ENCRYPT_PASSWORD}'] = $configuration['password_encryption'];

        $config['SESSION_LIFETIME'] = 3600;
        $config['{NEW_VERSION}'] = $this->getLatestVersion();
        $config['NEW_VERSION_STABLE'] = 'true';

        foreach ($config as $key => $value) {
            $contents = str_replace($key, $value, $contents);
        }
        $newConfigurationFile = $configurationPath.'configuration.php';

        return file_put_contents($newConfigurationFile, $contents);
    }


    /**
     * Updates the configuration.yml file
     * @param string $version
     *
     * @return bool
     */
    public function updateConfiguration($version)
    {
        global $userPasswordCrypted, $storeSessionInDb;

        $_configuration = $this->getHelper('configuration')->getConfiguration();

        $configurationPath = $this->getHelper('configuration')->getConfigurationPath();

        $dumper = new Dumper();

        $_configuration['system_version'] = $version;

        if (!isset($_configuration['password_encryption'])) {
            $_configuration['password_encryption']      = $userPasswordCrypted;
        }

        if (!isset($_configuration['session_stored_in_db'])) {
            $_configuration['session_stored_in_db']     = $storeSessionInDb;
        }

        $yaml = $dumper->dump($_configuration, 2); //inline
        $newConfigurationFile = $configurationPath.'../../../app/config/configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);

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

}
