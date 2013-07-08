<?php

namespace Chash\Command\Installation;

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

        // Getting the new config folder
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($path);

        $this->setRootSys(realpath($configurationPath.'/../').'/');

        $dialog = $this->getHelperSet()->get('dialog');

        $defaultVersion = $this->getLatestVersion();

        if (empty($version)) {
            $version = $defaultVersion;
        }

        if ($this->commandLine) {
            $output->writeln("<comment>Welcome to the Chamilo installation process.</comment>");
        } else {
            $output->writeln("<comment>Chamilo installation process. </comment>");
        }

        if (empty($configurationPath)) {
            $output->writeln("<error>There's an error while loading the configuration path. Are you sure this is a Chamilo folder?</error>");
            return 0;
        }

        if (!is_writable($configurationPath)) {
            $output->writeln("<error>Folder ".$configurationPath." must be writable</error>");
            return 0;
        }

        $sqlFolder = $this->getInstallationPath($version);

        if (!is_dir($sqlFolder)) {
            $output->writeln("<comment>Sorry you can't install that version of Chamilo :(.</comment>");
            $output->writeln("<comment>Supported versions:</comment> <info>".implode(', ', $this->getAvailableVersions()));
            return 0;
        }

        if (file_exists($configurationPath.'configuration.php') || file_exists($configurationPath.'configuration.yml')) {
            if ($this->commandLine) {
                $output->writeln("<comment>There's a Chamilo portal here:</comment> <info>".$configurationPath."</info>");
                $output->writeln("<comment>You should run <info>chamilo:setup </info><comment>if you want to start with a fresh install.</comment>");
            } else {
                $output->writeln("<comment>There's a Chamilo portal here:</comment> <info>".$configurationPath." </info>");
            }
            return 0;
            /*
            if (!$dialog->askConfirmation(
                $output,
                '<question>There is a Chamilo installation located here:</question> '.$configurationPath.' <question>Are you sure you want to continue?</question>(y/N)',
                false
            )
            ) {
                return 0;
            }

            if (!$dialog->askConfirmation(
                $output,
                '<comment>This will be a fresh installation. Old databases and config files will be deleted. </comment></info> <question>Are you sure?</question>(y/N)',
                false
            )
            ) {
                return 0;
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
            $total = count($params);
            $portalSettings = array();

            $output->writeln("<comment>Portal settings (".$total.") </comment>");

            $counter = 1;
            foreach ($params as $key => $value) {
                $data = $dialog->ask(
                    $output,
                    "($counter/$total) Please enter the value of the $key (".$value['attributes']['data']."): ",
                    $value['attributes']['data']
                );
                $counter++;
                $portalSettings[$key] = $data;
            }
            $this->setPortalSettings($portalSettings);

            // Ask for admin settings
            $params = $this->getAdminSettingsParams();
            $total = count($params);
            $output->writeln("<comment>Admin settings: (".$total.")</comment>");
            $adminSettings = array();
            $counter = 1;
            foreach ($params as $key => $value) {
                $data = $dialog->ask(
                    $output,
                    "($counter/$total) Please enter the value of the $key (".$value['attributes']['data']."): ",
                    $value['attributes']['data']
                );
                $counter++;
                $adminSettings[$key] = $data;
            }
            $this->setAdminSettings($adminSettings);

            // Ask for db settings
            $params = $this->getDatabaseSettingsParams();
            $total = count($params);
            $output->writeln("<comment>Database settings: (".$total.")</comment>");
            $databaseSettings = array();
            $counter = 1;
            foreach ($params as $key => $value) {
                $data = $dialog->ask(
                    $output,
                    "($counter/$total) Please enter the value of the $key (".$value['attributes']['data']."): ",
                    $value['attributes']['data']
                );
                $counter++;
                $databaseSettings[$key] = $data;
            }
            $this->setDatabaseSettings($databaseSettings);
        }

        $databaseSettings = $this->getDatabaseSettings();
        $connectionToHost = $this->getUserAccessConnectionToHost();

        $connectionToHostConnect = $connectionToHost->connect();

        if ($connectionToHostConnect) {
            $output->writeln("<comment>Connection enabled for user: </comment><info>".$databaseSettings['user']);
        } else {
            $output->writeln("<error>No access to the database for user:</error><info>".$databaseSettings['user']."</info>");
            return 0;
        }

        if ($this->commandLine) {
            $eventManager = $connectionToHost->getSchemaManager();
            $databases = $eventManager->listDatabases();
            if (in_array($databaseSettings['dbname'], $databases)) {
                $dialog = $this->getHelperSet()->get('dialog');

                if (!$dialog->askConfirmation(
                    $output,
                    '<comment>The database <info>'.$databaseSettings['dbname'].'</info> exists and is going to be dropped!</comment> <question>Are you sure?</question>(y/N)',
                    false
                )
                ) {
                    return 0;
                }
            }
        }

        // When installing always drop the current database
        try {
            $sm = $connectionToHost->getSchemaManager();
            $sm->dropAndCreateDatabase($databaseSettings['dbname']);
            $connectionToDatabase = $this->getUserAccessConnectionToDatabase();
            $connect = $connectionToDatabase->connect();
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not create database for connection named <comment>%s</comment></error>', $databaseSettings['dbname']));
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return 0;
        }

        if ($connect) {

            $output->writeln("<comment>Connection to database '".$databaseSettings['dbname']."' established.</comment>");

            $configurationWasSaved = $this->writeConfiguration($version, $path);

            if ($configurationWasSaved) {

                // $app['chamilo.log'] = $app['log.path'].'/chamilo_install.log';

                // Installing database
                $result = $this->install($version, $output);

                if ($result) {
                    // Injecting the chamilo application (because the configuration.php is now set)

                    $app = require_once $this->getRootSys().'main/inc/global.inc.php';
                    $app['session.test'] = true;
                    $filesystem = $app['chamilo.filesystem'];

                    // Creating temp folders
                    $filesystem->createFolders($app['temp.paths']->folders);
                    $output->writeln("<comment>Temp folders were created.</comment>");

                    $app['installer']->setSettingsAfterInstallation($this->getAdminSettings(), $this->getPortalSettings());

                    //$app->run();

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
                    //$output->writeln("<comment>Migration ended successfully</comment>");

                    //$output->writeln("<comment>Chamilo was successfully installed. Go to your browser and enter:</comment> <info>".$newConfigurationArray['root_web']);
                    return 1;
                } else {
                    $output->writeln("<comment>There was an error during installation.</comment>");
                    return 0;
                }
            } else {
                $output->writeln("<comment>Configuration file was not saved</comment>");
                return 0;
            }
        } else {
            $output->writeln("<comment>Can't create database '".$databaseSettings['dbname']."' </comment>");
            return 0;
        }
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
     * Installs Chamilo
     *
     * @param string $version
     * @param array $_configuration
     * @param $output
     * @return bool
     */
    public function install($version, $output)
    {
        $this->setDoctrineSettings();
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

            if ($this->commandLine) {
                $output->writeln("<comment>Check your installation status with </comment><info>chamilo:status</info>");
            }

            return true;
        }

        return false;
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
        $conn = $this->testUserAccessConnection();
        $connect = $conn->connect();
        return $connect;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getUserAccessConnectionToHost()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $databaseConnection = $this->getDatabaseSettings();
        $databaseConnection['dbname'] = null;
        $conn = \Doctrine\DBAL\DriverManager::getConnection($databaseConnection, $config);
        return $conn;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getUserAccessConnectionToDatabase()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $databaseConnection = $this->getDatabaseSettings();
        $conn = \Doctrine\DBAL\DriverManager::getConnection($databaseConnection, $config);
        return $conn;
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
}
