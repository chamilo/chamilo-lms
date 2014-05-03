<?php

namespace Chash\Helpers;

use Guzzle\Tests\Batch\ExceptionBufferingBatchTest;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Finder\Finder;

/**
 * Class ConfigurationHelper
 * @package Chash\Helpers
 */
class ConfigurationHelper extends Helper
{
    protected $configuration;
    protected $sysPath;
    protected $dryRun = false;

    /**
     *
     */
    public function __construct()
    {

    }

    /**
     * @return bool
     */
    public function getDryRun()
    {
        return $this->dryRun;
    }

    /**
     * @param $dryRun
     */
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Get chamilo versions
     * @return array
     */
    public function chamiloVersions()
    {
        $versionList = array(
            '1.8.6.2',
            '1.8.7',
            '1.8.8',
            '1.8.8.2',
            '1.8.8.4',
            '1.8.8.6',
            '1.9.0',
            '1.9.2',
            '1.9.4',
            '1.9.6',
            '1.9.8',
            '10.0.0',
            '11.0.0'
        );

        return $versionList;
    }

    /**
     * Gets the configuration file path from the Chamilo installation
     * <code>
     * $configurationPath = $this->getConfigurationPath('/var/www/chamilo');
     * // $configurationPath value is '/var/www/chamilo/config/'; or
     * // $configurationPath value is '/var/www/chamilo/inc/conf/'; or
     * </code>
     * @param string $path the path of the Chamilo installation
     * @return bool|string @example /var/www/chamilo/config/configuration.php
     */
    public function getConfigurationPath($path = null)
    {
        if (empty($path)) {
            $chamiloPath = getcwd();
        } else {
            $chamiloPath = $path;
        }

        if (is_dir($chamiloPath.'/main/inc/conf')) {
            return realpath($chamiloPath.'/main/inc/conf/').'/';
        }

        if (is_dir($chamiloPath.'/config')) {
            return realpath($chamiloPath.'/config/').'/';
        }

        return false;
    }

    /**
     * Get the new configuration file from the Chamilo installation
     * <code>
     * $newConfigurationPath = $this->getNewConfigurationPath('/var/www/chamilo');
     * // $newConfigurationPath value is '/var/www/chamilo/config/configuration.php';
     * </code>
     * @param string $path the path of the Chamilo installation
     * @return bool|string  @example /var/www/chamilo/config/configuration.php
     */
    public function getNewConfigurationPath($path = null)
    {
        if (empty($path)) {
            $chamiloPath = getcwd();
        } else {
            $chamiloPath = $path;
        }

        if (is_dir($chamiloPath.'/config/')) {
            return $dir = realpath($chamiloPath.'/config/').'/';
        }

        return false;
    }

    /**
     * Converts /var/www/chamilo/main/inc/conf to /var/www/chamilo/config
     * @param string $path
     * @return string new path
     */
    public function convertOldConfigurationPathToNewPath($path)
    {
        return realpath($path.'../../../').'/config/';
    }

    /**
     * Gets the configuration file path
     * <code>
     * $configurationPath = $this->getConfigurationFilePath('/var/www/chamilo')
     * // $configurationPath value is '/var/www/chamilo/config/configuration.php'; or
     * // $configurationPath value is '/var/www/chamilo/main/inc/conf/configuration.php';
     * </code>
     * @param string $path the path of the Chamilo installation
     * @return bool|string returns
     *
     */
    public function getConfigurationFilePath($path = null)
    {
        $confPath = $this->getConfigurationPath($path);

        if (!empty($confPath)) {

            if (file_exists($confPath.'configuration.php')) {
                return $confPath.'configuration.php';
            }

            if (file_exists($confPath.'configuration.yml')) {
                return $confPath.'configuration.yml';
            }
        }

        return false;
    }


    /**
     * Returns the $configuration array
     * <code>
     * $configuration = $this->getConfiguration('/var/www/chamilo');
     * // $configuration contains the $_configuration array
     * </code>
     * @param string $path
     *
     * @return array|bool|mixed
     */
    public function getConfiguration($path = null)
    {
        if (empty($this->configuration)) {
            $configurationFile = $this->getConfigurationFilePath($path);
            if ($configurationFile) {
                $this->configuration = $this->readConfigurationFile($configurationFile);
            }
        }
        return $this->configuration;
    }

    /**
     *
     * <code>
     * $sysPath = $this->getSysPathFromConfigurationFile('/var/www/chamilo/config/configuration.php');
     * // $sysPath is '/var/www/chamilo/'
     * </code>
     * @param string $configurationFile
     * @return string
     */
    public function getSysPathFromConfigurationFile($configurationFile)
    {
        if (empty($configurationFile)) {
            return false;
        }

        $configurationPath = dirname($configurationFile);

        // New structure
        if (file_exists($configurationPath.'/../main/install/index.php')) {
            return realpath($configurationPath.'/../').'/';
        }

        // Old structure
        if (file_exists($configurationPath.'/../../install/index.php')) {
            return realpath($configurationPath.'/../../../').'/';
        }

        return null;
    }


    /**
     * Reads the Chamilo configuration file and returns the $_configuration array
     * Merges the configuration.php with the configuration.yml if it exists
     *
     * @param string $configurationFile
     * @return array|bool|mixed
     */
    public function readConfigurationFile($configurationFile = null)
    {
        if (!empty($configurationFile)) {

            if (file_exists($configurationFile)) {
                $confInfo = pathinfo($configurationFile);
                switch ($confInfo['extension']) {
                    case 'php':
                        $temp = require $configurationFile;

                        // The file return the array?
                        if (!empty($temp)) {
                            $_configuration = $temp;
                        }

                        if (isset($_configuration)) {
                            if (isset($userPasswordCrypted)) {
                                $_configuration['password_encryption'] = $userPasswordCrypted;
                            }
                            return $_configuration;
                        }
                        break;
                    case 'yml':
                        $yaml = new Parser();
                        $_configurationYML = $yaml->parse(file_get_contents($configurationFile));
                        if (isset($configurationFile) && !empty($configurationFile)) {
                            if (isset($_configuration) && !empty($_configuration)) {
                                $_configuration = array_merge($_configuration, $_configurationYML);
                            } else {
                                $_configuration = $_configurationYML;
                            }
                        }
                        return $_configuration;
                        break;
                }
            }
        }
        return array();
    }

    /**
     * Sets the configuration variable
     * @param $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get chamilo config files
     * @return array
     */
    public function getConfigFiles()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();

        $configFiles = array(
            'auth.conf.php',
            'configuration.php',
            'configuration.yml',
            'events.conf.php',
            'mail.conf.php',
            'portfolio.conf.php',
            'profile.conf.php'
        );

        if (is_dir($sysPath.'main/inc/conf')) {
            $finder->files()->in($sysPath.'main/inc/conf');
            foreach ($configFiles as $config) {
                if (file_exists($sysPath.'main/inc/conf/'.$config)) {
                    $finder->files()->name($config);
                }
            }
            $finder->files()->name('db_migration_status_*');
        }

        if (is_dir($sysPath.'config')) {
            $finder->files()->in($sysPath.'config');
            foreach ($configFiles as $config) {
                if (file_exists($sysPath.'config/'.$config)) {
                    $finder->files()->name($config);
                }
            }
            $finder->files()->name('db_migration_status_*');
        }

        /*
        $versions = $this->chamiloVersions();
        foreach ($versions as $version) {
            $migrationFile = $sysPath."main/inc/conf/db_migration_status_".$version."_pre.yml";
            if (file_exists($migrationFile)) {
                $configFiles[] = $migrationFile;
            }
            $migrationFile = $sysPath."main/inc/conf/db_migration_status_".$version."_post.yml";
            if (file_exists($migrationFile)) {
                $configFiles[] = $migrationFile;
            }
        }
        */
        return $finder;
    }


    /**
     * @return array
     */
    public function getCoursesFiles()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();

        if (is_dir($sysPath.'courses')) {
            $finder->files()->in($sysPath.'courses/');
            $finder->directories()->in($sysPath.'courses/');
        }

        if (is_dir($sysPath.'data/courses')) {
            $finder->files()->in($sysPath.'data/courses/');
            $finder->directories()->in($sysPath.'data/courses/');
        }

        return $finder;
    }
    /**
     * Gets the documents and folders marked DELETED
     * @return array
     */
    public function getDeletedDocuments()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();

        if (is_dir($sysPath.'courses')) {
            $finder->in($sysPath.'courses/')->name('*DELETED*');
        }

        if (is_dir($sysPath.'data/courses')) {
            $finder->in($sysPath.'data/courses/')->name('*DELETED*');
        }

        return $finder;
    }

    /**
     * @return Finder
     */
    public function getSysFolders()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();
        $finder->directories()->in($sysPath);
        // Skipping files
        $finder->notPath('vendor');
        $finder->notPath('tests');
        return $finder;
    }

    /**
     * @return Finder
     */
    public function getSysFiles()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();
        $finder->files()->in($sysPath);
        $finder->notPath('vendor');
        $finder->notPath('tests');
        return $finder;
    }

    /**
     * @return Finder
     */
    public function getDataFolders()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();

        if (empty($sysPath)) {
            return null;
        }

        if (is_dir($sysPath.'courses')) {
            $finder->directories()->depth('== 0')->in($sysPath.'/courses');
        }

        if (is_dir($sysPath.'data/courses')) {
            $finder->directories()->depth('== 0')->in($sysPath.'/data/courses');
        }

        return $finder;
    }

    /**
     * @return Finder
     */
    public function getConfigFolders()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();
        $finder->directories()->in($sysPath);
        $finder->path('main/inc/conf');
        $finder->path('data/config');
        return $finder;
    }

    /**
     * Lists the directories in the archive/ or data/temp/ directory (depends on Chamilo version)
     * @return Finder
     */
    public function getTempFolders()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();
        if (is_dir($sysPath.'archive')) {
            $finder->directories()->in($sysPath.'archive/');
        }
        if (is_dir($sysPath.'data/temp')) {
            $finder->directories->in($sysPath.'data/temp/');
        }
        return $finder;
    }
    /**
     * Lists the files in the archive/ or data/temp/ directory (depends on Chamilo version)
     * @return Finder
     */
    public function getTempFiles()
    {
        $finder = new Finder();
        $sysPath = $this->getSysPath();
        if (is_dir($sysPath.'archive')) {
            $finder->in($sysPath.'archive/');
            $finder->files()->notName('index.*');
        }
        if (is_dir($sysPath.'data/temp')) {
            $finder->in($sysPath.'data/temp/');
            $finder->files()->notName('index.*');
        }
        return $finder;
    }

    /**
     * Sets the system's root path (e.g. /var/www/chamilo/)
     * @param $sysPath
     */
    public function setSysPath($sysPath)
    {
        $this->sysPath = $sysPath;
    }

    /**
     * @return array
     */
    public function getTempFolderList()
    {
        // Copied from the resources/prod.php file in Chamilo

        $sysPath = $this->getSysPath();

        $tempPath = 'archive/';
        if (is_dir($sysPath.'config')) {
            $tempPath = 'data/temp/';
        }

        $app['temp.paths'] = new \stdClass();

        //$app['temp.paths']->folders[] = $app['sys_data_path'];

        // Monolog.
        //$app['temp.paths']->folders[] = $app['sys_log_path'];
        $app['temp.path'] = $this->getSysPath().$tempPath;
        // Twig cache.
        $app['temp.paths']->folders[] = $app['twig.cache.path'] = $app['temp.path'].'twig';

        // Http cache
        $app['temp.paths']->folders[] = $app['http_cache.cache_dir'] = $app['temp.path'].'http';

        // Doctrine ORM.
        $app['temp.paths']->folders[] = $app['db.orm.proxies_dir'] = $app['temp.path'].'Proxies';

        // Symfony2 Web profiler.
        $app['temp.paths']->folders[] = $app['profiler.cache_dir'] = $app['temp.path'].'profiler';

        // HTMLPurifier.
        $app['temp.paths']->folders[] = $app['htmlpurifier.serializer'] = $app['temp.path'].'serializer';

        // PCLZIP temp dir.
        //define('PCLZIP_TEMPORARY_DIR', $app['temp.path'].'pclzip');
        $app['temp.paths']->folders[] = $app['temp.path'].'pclzip';

        // MPDF temp libs.
        //define("_MPDF_TEMP_PATH", $app['temp.path'].'mpdf');
        //define("_JPGRAPH_PATH", $app['temp.path'].'mpdf');
        //define("_MPDF_TTFONTDATAPATH", $app['temp.path'].'mpdf');

        $app['temp.paths']->folders[] = $app['temp.path'].'mpdf';

        // QR code.
        //define('QR_LOG_DIR', $app['temp.path'].'qr');
        //define('QR_CACHE_DIR', $app['temp.path'].'qr');

        $app['temp.paths']->folders[] = $app['temp.path'].'qr';

        // Chamilo Temp class @todo fix this
        $app['temp.paths']->folders[] = $app['temp.path'].'temp';

        return $app['temp.paths']->folders;
    }

    /**
     * @return string
     */
    public function getSysPath()
    {
        return $this->sysPath;
    }

    /**
     * Gets an array with all the databases (particularly useful for Chamilo <1.9)
     * @todo use connection instead of mysql_*
     * @return mixed Array of databases
     */
    public function getAllDatabases()
    {
        $_configuration = $this->getConfiguration();
        $dbs = array();

        $dbs[] = $_configuration['main_database'];

        if (isset($_configuration['statistics_database']) && !in_array(
                $_configuration['statistics_database'],
                $dbs
            ) && !empty($_configuration['statistics_database'])
        ) {
            $dbs[] = $_configuration['statistics_database'];
        }

        if (isset($_configuration['scorm_database']) && !in_array(
                $_configuration['scorm_database'],
                $dbs
            ) && !empty($_configuration['scorm_database'])
        ) {
            $dbs[] = $_configuration['scorm_database'];
        }

        if (isset($_configuration['user_personal_database']) && !in_array(
                $_configuration['user_personal_database'],
                $dbs
            ) && !empty($_configuration['user_personal_database'])
        ) {
            $dbs[] = $_configuration['user_personal_database'];
        }

        $courseTable = $_configuration['main_database'].'.course';

        $singleDatabase = isset($_configuration['single_database']) ? $_configuration['single_database'] : false;

        if ($singleDatabase == false) {
            $sql = 'SELECT db_name from '.$courseTable;
            $res = mysql_query($sql);
            if ($res && mysql_num_rows($res) > 0) {
                while ($row = mysql_fetch_array($res)) {
                    if (!empty($row['db_name'])) {
                        $dbs[] = $row['db_name'];
                    }
                }
            }
        }

        return $dbs;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'configuration';
    }

    /**
     * Gets the current install's major version. Requires getConfiguration() to be called first
     * @return  string  The major version (two-parts version number, e.g. "1.9")
     */
    public function getMajorVersion()
    {
        if (empty($this->configuration)) {
            $this->getConfiguration();
        }
        list($first, $second) = preg_split('/\./',$this->configuration['system_version']);
        return $first.'.'.$second;
    }
}
