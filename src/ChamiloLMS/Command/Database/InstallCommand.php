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
class InstallCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:install')
            ->setDescription('Execute a Chamilo installation to a specified version')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the chamilo folder');
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
        return api_get_path(SYS_PATH).'main/install/'.$version.'/';
    }

    /**
     * Gets the version name folders located in main/install
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $installPath = api_get_path(SYS_PATH).'main/install';
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
     * Executes a command via CLI
     *
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $path = $input->getOption('path');
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);

        $dialog = $this->getHelperSet()->get('dialog');

        $version = $input->getArgument('version');

        $defaultVersion = $this->getLatestVersion();

        if (empty($version)) {
            $version = $defaultVersion;
        }

        $output->writeln("<comment>Welcome to the Chamilo $version installation process.</comment>");

        if (!is_writable($configurationPath)) {
            $output->writeln("<comment>Folder ".$configurationPath." must be writable</comment>");
        }

        $sqlFolder = $this->getInstallationPath($version);

        if (!is_dir($sqlFolder)) {
            $output->writeln("<comment>Sorry you can't install that version of Chamilo :( Supported versions:</comment> <info>".implode(', ', $this->getAvailableVersions()));

            return false;
        }

        if (file_exists($configurationPath.'configuration.php') || file_exists($configurationPath.'configuration.yml')) {
            $output->writeln("<comment>There's a Chamilo portal here ".$configurationPath." you must run</comment> <info>chamilo:setup </info><comment>if you want a fresh install.</comment>");
            exit;
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

        //Getting default configuration parameters
        require_once api_get_path(SYS_PATH).'main/install/configuration.dist.yml.php';

        $avoidVariables = array(
            'db_glue',
            'code_append',
            'course_folder',
            'db_admin_path',
            'cdn_enable',
            'verbose_backup',
            'session_stored_in_db',
            'session_lifetime',
            'software_name',
            'software_url',
            'deny_delete_users',
            'system_version',
            'system_stable'
        );

        $newConfigurationArray = array();
        foreach ($_configuration as $key => $value) {
            if (in_array($key, $avoidVariables)) {
                $newConfigurationArray[$key] = $value;
                continue;
            }
            if (!is_array($value)) {
                $data = $dialog->ask(
                    $output,
                    "Please enter the value of the $key ($value): ",
                    $value
                );
                $newConfigurationArray[$key] = $data;
            } else {
                $newConfigurationArray[$key] = $value;
            }
        }

        $configurationWasSaved = $this->writeConfiguration($newConfigurationArray, $version);

        if ($configurationWasSaved) {

            //Installing database
            $result = $this->install($version, $newConfigurationArray, $output);

            if ($result) {
                $this->createAdminUser($newConfigurationArray, $output);

                $output->writeln("<comment>Chamilo was successfully installed. Go to your browser and enter:</comment> <info>".$newConfigurationArray['root_web']);
            }
        }
    }

    /**
     *
     * @param $newConfigurationArray
     * @param $output
     *
     * @return bool
     */
    public function createAdminUser($newConfigurationArray, $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        //Creating admin user
        $adminUser = array(
            'lastname' => 'Julio',
            'firstname' => 'M',
            'username' => 'admin',
            'password' => 'admin',
            'email' => 'admin@example.org',
            'language' => 'english',
            'phone' => '6666666'
        );

        $output->writeln("<comment>Creating an admin User</comment>");
        $userInfo = array();
        foreach ($adminUser as $key => $value) {
            $data = $dialog->ask(
                $output,
                "Please enter the $key ($value): ",
                $value
            );
            $userInfo[$key] = $data;
        }
        //By default admin is = 1 so we update it
        $userId = $userInfo['user_id'] = 1;
        $userInfo['auth_source'] = 'platform';
        $userInfo['password'] = api_get_encrypted_password($userInfo['password']);

        $result = \UserManager::update($userInfo);
        if ($result) {
            \UserManager::add_user_as_admin($userInfo['user_id']);
            $output->writeln("<comment>User admin created with id: $userId</comment>");

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getLatestVersion()
    {
        return '1.10';
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
        $dumper = new Dumper();
        $yaml = $dumper->dump($newConfigurationArray, 2); //inline
        $newConfigurationFile = $configurationPath.'configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);
    }

    private function setDatabaseSettings($_configuration)
    {
        global $config;

        $defaultConnection = array(
            'driver'    => 'pdo_mysql',
            'dbname'    => $_configuration['main_database'],
            'user'      => $_configuration['db_user'],
            'password'  => $_configuration['db_password'],
            'host'      => $_configuration['db_host'],
        );

        $em = \Doctrine\ORM\EntityManager::create($defaultConnection, $config);

        //Fixes some errors
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $platform->registerDoctrineTypeMapping('set', 'string');

        $helpers = array(
            'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
            'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
        );

        foreach ($helpers as $name => $helper) {
            $this->getApplication()->getHelperSet()->set($helper, $name);
        }

        $conn_return = @\Database::connect(
        array(
            'server' => $_configuration['db_host'],
            'username' => $_configuration['db_user'],
            'password' => $_configuration['db_password'],
           // 'persistent' => $_configuration['db_persistent_connection']
            // When $_configuration['db_persistent_connection'] is set, it is expected to be a boolean type.
        ));

        global $database_connection;
        $checkConnection = @\Database::select_db($_configuration['main_database'], $database_connection);
    }

    /**
     * Installs Chamilo
     *
     * @param string $version
     * @param array $_configuration
     * @param $output
     * @return bool
     */
    public function install($version, $_configuration, $output)
    {
        $sqlFolder = $this->getInstallationPath($version);

        $this->setDatabaseSettings($_configuration);

        $testConnection = $this->testDatabaseConnection($_configuration['db_host'], $_configuration['db_user'], $_configuration['db_password']);

        if ($testConnection == 1) {
            $output->writeln("<comment>Connection stablished with the DB</comment>");
        } else {
            $output->writeln("<error>No access to the database for user:</error><info>".$_configuration['db_user']."</info>");
            exit;
        }

        $output->writeln("<comment>Creating database ... </comment>");
        $result = $this->dropAndCreateDatabase($_configuration);

        //Importing files
        if ($result) {
            $command = $this->getApplication()->find('dbal:import');

            //Importing sql files
            $arguments = array(
                'command' => 'dbal:import',
                'file' => array(
                    $sqlFolder.'db_main.sql',
                    $sqlFolder.'db_stats.sql',
                    $sqlFolder.'db_user.sql',
                    $sqlFolder.'db_course.sql',
                )
            );
            $input = new ArrayInput($arguments);
            $command->run($input, $output);

            api_set_setting('Institution', 'Portal');
            api_set_setting('InstitutionUrl', 'Portal');
            api_set_setting('siteName', 'Campus');
            api_set_setting('emailAdministrator', 'admin@example.org');
            api_set_setting('administratorSurname', 'M');
            api_set_setting('administratorName', 'Julio');
            api_set_setting('platformLanguage', 'english');
            api_set_setting('allow_registration', '1');
            api_set_setting('allow_registration_as_teacher', '1');

            //Getting extra information about the installation
            //$value = api_get_setting('chamilo_database_version');
            //$output->writeln("<comment>Showing chamilo_database_version value:</comment> ".$value);
            $output->writeln("<comment>Check your installation status with </comment><info>chamilo:status</info>");
            $output->writeln("<comment>Database process ended!</comment>");

            return true;
        }
    }

    /**
     * Creates a Database
     * @todo use doctrine?
     *
     * @return resource
     */
    public function dropAndCreateDatabase($_configuration)
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

        $this->dropDatabase($_configuration);
        $result = \Database::query("CREATE DATABASE IF NOT EXISTS ".mysql_real_escape_string($_configuration['main_database'])." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci");

        if ($result) {

            return \Database::select_db($_configuration['main_database']);
        }

        return false;
    }

    public function dropDatabase($_configuration)
    {
        \Database::query("DROP DATABASE ".mysql_real_escape_string($_configuration['main_database'])."");
    }

    /**
     * In step 3. Tests establishing connection to the database server.
     * If it's a single database environment the function checks if the database exist.
     * If the database doesn't exist we check the creation permissions.
     *
     * @return int      1 when there is no problem;
     *                  0 when a new database is impossible to be created, then the single/multiple database configuration is impossible too
     *                 -1 when there is no connection established.
     */
    public function testDatabaseConnection($dbHostForm, $dbUsernameForm, $dbPassForm)
    {
        $dbConnect = -1;
        //Checking user credentials
        if (@\Database::connect(
            array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm)
        ) !== false
        ) {
            $dbConnect = 1;
        } else {
            $dbConnect = -1;
        }

        return $dbConnect; //return 1, if no problems, "0" if, in case we can't create a new DB and "-1" if there is no connection.
    }
}