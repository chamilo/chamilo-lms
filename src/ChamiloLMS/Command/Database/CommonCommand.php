<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Command\Database;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;

class CommonCommand extends AbstractCommand
{
    public $portalSettings;
    public $databaseSettings;
    public $adminSettings;
    public $rootSys;

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
     * Gets the version name folders located in main/install
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $installPath = $this->getRootSys().'main/install';
        $dir = new \DirectoryIterator($installPath);
        $dirList = array();
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $dirList[] = $fileInfo->getFilename();
            }
        }

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
                    'label' => 'Password',
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
                    'data' => 'chamilo',
                ),
                'type' => 'text'
            ),
            'user' => array(
                'attributes' => array(
                    'label' => 'URL',
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
     * @return bool
     *
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

        $configuration['root_web'] = $portalSettings['institution_url'];
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

        $config['{ROOT_WEB}'] = $portalSettings['institution_url'];
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
