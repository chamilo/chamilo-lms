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
        return api_get_path(SYS_PATH).'main/install/'.$version.'/';
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
     * @param $newConfigurationArray
     * @param $version
     */
    public function writeConfiguration($newConfigurationArray, $version)
    {
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath();

        $newConfigurationArray['system_version'] = $version;
        $newConfigurationArray['db_glue'] = '`.`';
        $newConfigurationArray['db_prefix'] = '';

        $dumper = new Dumper();
        $yaml = $dumper->dump($newConfigurationArray, 2); //inline
        $newConfigurationFile = $configurationPath.'../../../config/configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);
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

}