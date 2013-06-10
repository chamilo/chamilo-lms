<?php

namespace ChamiloLMS\Command\Database;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Yaml\Dumper;

/**
 * Class InstallCommand
 */
class InstallCommand extends CommonCommand
{
    public $commandLine = true;


    /**
     * @return string
     */
    public function getLatestVersion()
    {
        return '1.10.0';
    }

    protected function configure()
    {
        $this
            ->setName('chamilo:install')
            ->setDescription('Execute a Chamilo installation to a specified version')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null)
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to the chamilo folder');
    }

    /**
     * Executes a command via CLI
     *
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        if (PHP_SAPI != 'cli') {
            $this->commandLine = false;
        }

        // Arguments
        $path = $input->getArgument('path');
        $version = $input->getArgument('version');

        // Setting configuration helper
        $this->getApplication()->getHelperSet()->set(new \Chash\Helpers\ConfigurationHelper(), 'configuration');

        //$configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);

        // Getting the new config folder
        $configurationPath = $this->getHelper('configuration')->getNewConfigurationPath($path);
        $this->setRootSys(realpath($configurationPath.'/../').'/');

        $dialog = $this->getHelperSet()->get('dialog');

        $defaultVersion = $this->getLatestVersion();

        if (empty($version)) {
            $version = $defaultVersion;
        }

        $output->writeln("<comment>Welcome to the Chamilo $version installation process.</comment>");

        if (!is_writable($configurationPath)) {
            $output->writeln("<comment>Folder ".$configurationPath." must be writable</comment>");
            return false;
        }

        $sqlFolder = $this->getInstallationPath($version);

        if (!is_dir($sqlFolder)) {
            $output->writeln("<comment>Sorry you can't install that version of Chamilo :( Supported versions:</comment> <info>".implode(', ', $this->getAvailableVersions()));
            return false;
        }

        if (file_exists($configurationPath.'configuration.php') || file_exists($configurationPath.'configuration.yml')) {
            $output->writeln("<comment>There's a Chamilo portal here ".$configurationPath." you must run</comment> <info>chamilo:setup </info><comment>if you want a fresh install.</comment>");
            return false;
            /*
            if (!$dialog->askConfirmation(
                $output,
                '<question>There is a Chamilo installation located here:</question> '.$configurationPath.' <question>Are you sure you want to continue?</question>(y/N)',
                false
            )
            ) {
                return;
            }

            if (!$dialog->askConfirmation(
                $output,
                '<comment>This will be a fresh installation. Old databases and config files will be deleted. </comment></info> <question>Are you sure?</question>(y/N)',
                false
            )
            ) {
                return;
            }
            $this->cleanInstallation($output);*/
        }

        $avoidVariables = array(
            //'main_database', //default is chamilo
            'db_glue',
            'table_prefix',
            'course_folder',
            'db_admin_path',
            'cdn_enable',
            'verbose_backup',
            'session_stored_in_db',
            'session_lifetime',
            'deny_delete_users',
            'system_version',
        );

        if ($this->commandLine) {

            // Ask for portal settings

            $params = $this->getPortalSettingsParams();
            $portalSettings = array();

            $output->writeln("<comment>Portal settings: </comment>");

            foreach ($params as $key => $value) {
                $data = $dialog->ask(
                    $output,
                    "Please enter the value of the $key (".$value['attributes']['data']."): ",
                    $value['attributes']['data']
                );
                $portalSettings[$key] = $data;
            }
            $this->setPortalSettings($portalSettings);

            // Ask for admin settings
            $output->writeln("<comment>Admin settings: </comment>");
            $params = $this->getAdminSettingsParams();
            $adminSettings = array();

            foreach ($params as $key => $value) {
                $data = $dialog->ask(
                    $output,
                    "Please enter the value of the $key (".$value['attributes']['data']."): ",
                    $value['attributes']['data']
                );
                $adminSettings[$key] = $data;
            }
            $this->setAdminSettings($adminSettings);

            // Ask for db settings
            $output->writeln("<comment>Database settings: </comment>");
            $params = $this->getDatabaseSettingsParams();
            $databaseSettings = array();

            foreach ($params as $key => $value) {
                $data = $dialog->ask(
                    $output,
                    "Please enter the value of the $key (".$value['attributes']['data']."): ",
                    $value['attributes']['data']
                );
                $databaseSettings[$key] = $data;
            }
            $this->setDatabaseSettings($databaseSettings);
        }

        $testConnection = $this->testDatabaseConnection();

        if ($testConnection) {
            $output->writeln("<comment>Connection enabled for user: </comment><info>".$this->databaseSettings['user']);
        } else {
            $output->writeln("<error>No access to the database for user:</error><info>".$this->databaseSettings['user']."</info>");
        }

        $configurationWasSaved = $this->writeConfiguration($version);

        if ($configurationWasSaved) {

            // $app['chamilo.log'] = $app['log.path'].'/chamilo_install.log';

            // Installing database
            $result = $this->install($version, $output);

            if ($result) {

                require_once $this->getRootSys().'main/inc/lib/database.constants.inc.php';
                require_once $this->getRootSys().'main/inc/lib/main_api.lib.php';

                // In order to use the Datbase class
                $database = new \Database($this->getHelper('db')->getConnection());

                $this->createAdminUser($output);

                //@todo ask this during installation

                $adminInfo = $this->getAdminSettings();
                $portalSettings = $this->getPortalSettings();

                api_set_setting('emailAdministrator', $adminInfo['email']);
                api_set_setting('administratorSurname', $adminInfo['lastname']);
                api_set_setting('administratorName', $adminInfo['firstname']);
                api_set_setting('platformLanguage', $adminInfo['language']);

                api_set_setting('allow_registration', '1');
                api_set_setting('allow_registration_as_teacher', '1');

                api_set_setting('permissions_for_new_directories', $portalSettings['permissions_for_new_directories']);
                api_set_setting('permissions_for_new_files', $portalSettings['permissions_for_new_files']);

                api_set_setting('Institution', $portalSettings['institution']);
                api_set_setting('InstitutionUrl', $portalSettings['institution_url']);
                api_set_setting('siteName', $portalSettings['sitename']);

                //$versionInfo = $this->getAvailableVersionInfo($version);

                // Optional run Doctrine migrations from src/database/migrations
                /* $command = $this->getApplication()->find('migrations:migrate');
                $definition = $command->getDefinition();

                $arguments = array(
                    'command' => 'migrations:migrate',
                    'version' => $versionInfo['hook_to_doctrine_version'],
                    '--configuration' => $this->getMigrationConfigurationFile()
                );
                $output->writeln("<comment>Executing migrations:migrate ".$versionInfo['hook_to_doctrine_version']." --configuration=".$this->getMigrationConfigurationFile()."<comment>");

                $input = new ArrayInput($arguments, $definition);
                $return = $command->run($input, $output);
                */
                //$output->writeln("<comment>Migration ended succesfully</comment>");

                //$output->writeln("<comment>Chamilo was successfully installed. Go to your browser and enter:</comment> <info>".$newConfigurationArray['root_web']);
            }
        } else {
            $output->writeln("<comment>Configuration file was not saved</comment>");
        }
    }

    /**
     * Creates an admin user
     *
     * @param $newConfigurationArray
     * @param $output
     *
     * @return bool
     */
    public function createAdminUser($output)
    {

        //By default admin is = 1 so we update it

        $userInfo = $this->getAdminSettings();
        $userInfo['user_id'] = 1;
        $userInfo['auth_source'] = 'platform';
        $userInfo['password'] = $this->encryptPassword($this->portalSettings['encrypt_method'], $userInfo['password']);

        $result = \UserManager::update($userInfo);
        if ($result) {
            \UserManager::add_user_as_admin($userInfo['user_id']);
            $output->writeln("<comment>User admin created with id: 1</comment>");

            return true;
        }

        return false;
    }

    private function setDoctrineSettings()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $reader = new AnnotationReader();

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array());
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        $em = \Doctrine\ORM\EntityManager::create($this->databaseSettings, $config);

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
     * Installs Chamilo
     *
     * @param string $version
     * @param array $_configuration
     * @param $output
     * @return bool
     */
    public function install($version, $output)
    {
        $this->setDoctrineSettings($this->databaseSettings);

        $configurationPath = $this->getHelper('configuration')->getConfigurationPath(null);

        $sqlFolder = $this->getInstallationPath($version);

        $databaseMap = $this->getDatabaseMap();

        if (isset($databaseMap[$version])) {
            $dbInfo = $databaseMap[$version];
            $sections = $dbInfo['section'];

            foreach ($sections as $sectionData) {
                foreach ($sectionData as $dbInfo) {
                    $databaseName = $dbInfo['name'];
                    $dbList = $dbInfo['sql'];

                    $output->writeln("<comment>Creating database</comment> <info>$databaseName ... </info>");

                    // Fixing db list
                    foreach ($dbList as &$db) {
                        $db = $sqlFolder.$db;
                    }

                    $command = $this->getApplication()->find('dbal:import');

                    //Importing sql files
                    $arguments = array(
                        'command' => 'dbal:import',
                        'file' =>  $dbList
                    );
                    $input = new ArrayInput($arguments);
                    $command->run($input, $output);

                    //Getting extra information about the installation
                    $output->writeln("<comment>Database </comment><info>$databaseName </info><comment>process ended!</comment>");
                }
            }

            if (isset($sections) && isset($sections['course'])) {
                //@todo fix this
                foreach ($sections['course'] as $courseInfo) {
                    $databaseName = $courseInfo['name'];
                    $output->writeln("Inserting course database in chamilo: <info>$databaseName</info>");
                    $this->createCourse($databaseName);
                }
            }

            $output->writeln("<comment>Check your installation status with </comment><info>chamilo:status</info>");


            return true;
        }

        return false;
    }

    /**
     * Creates a course (only an insert in the DB)
     * @param string $databaseName
     */
    public function createCourse($databaseName)
    {
        $params = array(
            'code' => $databaseName,
            'db_name' => $databaseName,
            'course_language' => 'english',
            'title' => $databaseName,
            'visual_code' => $databaseName
        );
        @\Database::insert(TABLE_MAIN_COURSE, $params);
    }

    /**
     * Creates a Database
     * @todo use doctrine?
     *
     * @return resource
     */
    public function dropAndCreateDatabase($databaseName)
    {
        /*
        $command = $this->getApplication()->find('orm:schema-tool:create');
        $arguments = array(
            'command' => 'orm:schema-tool:create',
        );
        $input     = new ArrayInput($arguments);
        $command->run($input, $output);
        exit;
        */

        $this->dropDatabase($databaseName);
        $result = \Database::query("CREATE DATABASE IF NOT EXISTS ".mysql_real_escape_string($databaseName)." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci");

        if ($result) {
            return \Database::select_db($databaseName);
        }

        return false;
    }

    /**
     * Drops a database
     * @param string $name
     */
    public function dropDatabase($name)
    {
        \Database::query("DROP DATABASE ".mysql_real_escape_string($name)."");
    }

    /**
     *
     * In step 3. Tests establishing connection to the database server.
     * If it's a single database environment the function checks if the database exist.
     * If the database doesn't exist we check the creation permissions.
     *
     * @return int      1 when there is no problem;
     *                  0 when a new database is impossible to be created, then the single/multiple database configuration is impossible too
     *                 -1 when there is no connection established.
     */
    public function testDatabaseConnection()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $conn = \Doctrine\DBAL\DriverManager::getConnection($this->databaseSettings, $config);
        $connect = $conn->connect();
        return $connect;
    }

    /**
     * This function gets the hash in md5 or sha1 (it depends in the platform config) of a given password
     * @param  string password
     * @return string password with the applied hash
     */
    function encryptPassword($encryptionMode, $password, $salt = '') {

        switch ($encryptionMode) {
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
