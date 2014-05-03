<?php

namespace Chash\Command\Installation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console;
use Symfony\Component\Yaml\Dumper;

/**
 * Class InstallCommand
 * @package Chash\Command\Installation
 */
class InstallCommand extends CommonCommand
{
    public $commandLine = true;
    public $oldConfigLocation = false;

    protected function configure()
    {
        $this
            ->setName('chamilo:install')
            ->setDescription('Execute a Chamilo installation to a specified version.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null)
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to the chamilo folder')
            ->addOption('download-package', null, InputOption::VALUE_NONE, 'Downloads the chamilo package')
            ->addOption('temp-folder', null, InputOption::VALUE_OPTIONAL, 'The temp folder.', '/tmp')
            ->addOption('linux-user', null, InputOption::VALUE_OPTIONAL, 'user', 'www-data')
            ->addOption('linux-group', null, InputOption::VALUE_OPTIONAL, 'group', 'www-data')
            ->addOption('silent', null, InputOption::VALUE_NONE, 'Execute the migration with out asking questions.');

        $params = $this->getPortalSettingsParams();

        foreach ($params as $key => $value) {
            $this->addOption($key, null, InputOption::VALUE_OPTIONAL);
        }

        $params = $this->getAdminSettingsParams();
        foreach ($params as $key => $value) {
            $this->addOption($key, null, InputOption::VALUE_OPTIONAL);
        }

        $params = $this->getDatabaseSettingsParams();
        foreach ($params as $key => $value) {
            $this->addOption($key, null, InputOption::VALUE_OPTIONAL);
        }
    }

    /**
     * Executes a command via CLI
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Test string
        // sudo php /var/www/chash/chash.php  chamilo:install --download-package --sitename=Chamilo --institution=Chami --institution_url=http://localhost/chamilo-test --encrypt_method=sha1 --permissions_for_new_directories=0777 --permissions_for_new_files=0777 --firstname=John --lastname=Doe --username=admin --password=admin --email=admin@example.com --language=english --phone=666 --driver=pdo_mysql --host=localhost --port=3306 --dbname=chamilo_test --dbuser=root --dbpassword=root master /var/www/chamilo-test
        if (PHP_SAPI != 'cli') {
            $this->commandLine = false;
        }

        // Arguments
        $path = $input->getArgument('path');
        $version = $input->getArgument('version');

        $silent = $input->getOption('silent') == true;
        $download = $input->getOption('download-package');
        $tempFolder = $input->getOption('temp-folder');

        $linuxUser = $input->getOption('linux-user');
        $linuxGroup = $input->getOption('linux-group');

        //$sqlFolder = $this->getInstallationPath($version);

        // @todo fix process in order to install minor versions: 1.9.6
        $versionList = $this->getVersionNumberList();
        if (!in_array($version, $versionList)) {
            $output->writeln("<comment>Sorry you can't install version: '$version' of Chamilo :(</comment>");
            $output->writeln("<comment>Supported versions:</comment> <info>".implode(', ', $this->getVersionNumberList()));
            return 0;
        }

        if ($download) {
            $chamiloLocationPath = $this->getPackage($output, $version, null, $tempFolder);
            if (empty($chamiloLocationPath)) {
                return 0;
            }

            $result = $this->copyPackageIntoSystem($output, $chamiloLocationPath, $path);
            if ($result == 0) {
                return 0;
            }
        }

        // Setting configuration helper.
        $this->getApplication()->getHelperSet()->set(new \Chash\Helpers\ConfigurationHelper(), 'configuration');

        // Getting the new config folder.
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($path);

        // @todo move this in the helper

        if ($configurationPath == false) {
            //  Seems an old installation!
            $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);
            $this->setRootSys(realpath($configurationPath.'/../../../').'/');
            $this->oldConfigLocation = true;
        } else {
            // Chamilo installations > 1.10
            $this->oldConfigLocation = false;
            $this->setRootSys(realpath($configurationPath.'/../').'/');
        }

        $this->setConfigurationPath($configurationPath);

        $dialog = $this->getHelperSet()->get('dialog');

        if ($this->commandLine) {
            $title = "Welcome to the Chamilo installation process.";
        } else {
            $title = "Chamilo installation process.";
        }

        $this->writeCommandHeader($output, $title);

        if (empty($configurationPath)) {
            $output->writeln("<error>There's an error while loading the configuration path. Are you sure this is a Chamilo path?</error>");
            $output->writeln("<comment>Try setting up a Chamilo path for example: </comment> <info>chamilo:install 10 /var/www/chamilo</info>");
            $output->writeln("<comment>You can also *download* a Chamilo package adding the --download-package option:</comment>");
            $output->writeln("<info>chamilo:install 10 /var/www/chamilo --download-package</info>");
            return 0;
        }

        if (!is_writable($configurationPath)) {
            $output->writeln("<error>Folder ".$configurationPath." must be writable</error>");
            return 0;
        } else {
            $output->writeln("<comment>Configuration file will be saved here: </comment><info>".$configurationPath." </info>");
        }

        $configurationDistExists = false;

        if (file_exists($this->getRootSys().'config/configuration.dist.php')) {
            $configurationDistExists = true;
        } else {
            // Try the old one
            if (file_exists($this->getRootSys().'main/install/configuration.dist.php')) {
                $configurationDistExists = true;
            }
        }

        if ($configurationDistExists == false) {
            $output->writeln("<error>configuration.dist.php file nof found</error> <comment>The file must exists in install/configuration.dist.php or config/configuration.dist.php");
            return 0;
        }

        if (file_exists($configurationPath.'configuration.php') || file_exists($configurationPath.'configuration.yml')) {
            if ($this->commandLine) {
                $output->writeln("<comment>There's a Chamilo portal here:</comment> <info>".$configurationPath."</info>");
                $output->writeln("<comment>You should run <info>chamilo:wipe $path </info><comment>if you want to start with a fresh install.</comment>");
                $output->writeln("<comment>You could also manually delete this file:</comment><info> sudo rm ".$configurationPath."configuration.php</info>");

            } else {
                $output->writeln("<comment>There's a Chamilo portal here:</comment> <info>".$configurationPath." </info>");
            }
            return 0;
        }

        if ($this->commandLine) {

            // Ask for portal settings
            $filledParams = $this->getParamsFromOptions($input, $this->getPortalSettingsParams());

            $params = $this->getPortalSettingsParams();
            $total = count($params);
            $portalSettings = array();

            $output->writeln("<comment>Portal settings (".$total.") </comment>");

            $counter = 1;
            foreach ($params as $key => $value) {
                // If not in array ASK!
                if (!isset($filledParams[$key])) {
                    $data = $dialog->ask(
                        $output,
                        "($counter/$total) Please enter the value of the $key (".$value['attributes']['data']."): ",
                        $value['attributes']['data']
                    );
                    $counter++;
                    $portalSettings[$key] = $data;
                } else {
                    $output->writeln("($counter/$total) <comment>Option: $key = '".$filledParams[$key]."' was added as an option. </comment>");

                    $portalSettings[$key] = $filledParams[$key];
                    $counter++;
                }
            }
            $this->setPortalSettings($portalSettings);

            $filledParams = $this->getParamsFromOptions($input, $this->getAdminSettingsParams());

            // Ask for admin settings
            $params = $this->getAdminSettingsParams();
            $total = count($params);
            $output->writeln("<comment>Admin settings: (".$total.")</comment>");
            $adminSettings = array();
            $counter = 1;
            foreach ($params as $key => $value) {
                if (!isset($filledParams[$key])) {
                    $data = $dialog->ask(
                        $output,
                        "($counter/$total) Please enter the value of the $key (".$value['attributes']['data']."): ",
                        $value['attributes']['data']
                    );
                    $counter++;
                    $adminSettings[$key] = $data;
                } else {
                    $output->writeln("($counter/$total) <comment>Option: $key = '".$filledParams[$key]."' was added as an option. </comment>");
                    $counter++;
                    $adminSettings[$key] = $filledParams[$key];
                }
            }
            $this->setAdminSettings($adminSettings);

            // Ask for db settings
            $filledParams = $this->getParamsFromOptions($input, $this->getDatabaseSettingsParams());
            $params = $this->getDatabaseSettingsParams();
            $total = count($params);
            $output->writeln("<comment>Database settings: (".$total.")</comment>");
            $databaseSettings = array();
            $counter = 1;
            foreach ($params as $key => $value) {
                if (!isset($filledParams[$key])) {
                    $data = $dialog->ask(
                        $output,
                        "($counter/$total) Please enter the value of the $key (".$value['attributes']['data']."): ",
                        $value['attributes']['data']
                    );
                    $counter++;
                    $databaseSettings[$key] = $data;
                } else {
                    $output->writeln("($counter/$total) <comment>Option: $key = '".$filledParams[$key]."' was added as an option. </comment>");
                    $counter++;
                    $databaseSettings[$key] = $filledParams[$key];
                }
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
                if ($silent == false) {
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

                    // Read configuration file

                    $configurationFile = $this->getConfigurationHelper()->getConfigurationFilePath($this->getRootSys());
                    $configuration = $this->getConfigurationHelper()->readConfigurationFile($configurationFile);
                    $this->setConfigurationArray($configuration);

                    $configPath = $this->getConfigurationPath();
                    // Only works with 10 >=
                    $installChamiloPath = str_replace('config', 'main/install', $configPath);
                    $customVersion = $installChamiloPath.$version;

                    $output->writeln("Checking custom *update.sql* file in dir: ".$customVersion);
                    if (is_dir($customVersion)) {
                        $file = $customVersion.'/update.sql';
                        if (is_file($file) && file_exists($file)) {
                            $this->importSQLFile($file, $output);
                        }
                    } else {
                        $output->writeln("Nothing to update");
                    }

                    $this->setPortalSettingsInChamilo($output, $this->getHelper('db')->getConnection());
                    $this->setAdminSettingsInChamilo($output, $this->getHelper('db')->getConnection());

                    // Cleaning temp folders.
                    $command = $this->getApplication()->find('files:clean_temp_folder');
                    $arguments = array(
                        'command' => 'files:clean_temp_folder',
                        '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
                        //'--dry-run' => false
                    );

                    $input = new ArrayInput($arguments);
                    $command->run($input, $output);

                    // Generating temp folders.
                    $command = $this->getApplication()->find('files:generate_temp_folders');
                    $arguments = array(
                        'command' => 'files:generate_temp_folders',
                        '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
                        //'--dry-run' => false
                    );

                    $input = new ArrayInput($arguments);
                    $command->run($input, $output);

                    // Fixing permissions.

                    if (PHP_SAPI == 'cli') {
                        $command = $this->getApplication()->find('files:set_permissions_after_install');
                        $arguments = array(
                            'command' => 'files:set_permissions_after_install',
                            '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
                            '--linux-user' => $linuxUser,
                            '--linux-group' => $linuxGroup
                            //'--dry-run' => $dryRun
                        );

                        $input = new ArrayInput($arguments);
                        $command->run($input, $output);
                    }

                    // Generating config files (auth, profile, etc)
                    $this->generateConfFiles($output);

                    $output->writeln("<comment>Chamilo was successfully installed here: ".$this->getRootSys()." </comment>");
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

    /**
     * Get database version to install for a requested version
     * @param string $version
     * @return string
     */
    public function getVersionToInstall($version)
    {
        $newVersion = $this->getLatestVersion();
        switch ($version) {
            case '1.8.7':
                $newVersion = '1.8.7';
                break;
            case '1.8.8.0':
            case '1.8.8.6':
            case '1.8.8.8':
                $newVersion = '1.8.0';
                break;
            case '1.9.0':
            case '1.9.1':
            case '1.9.2':
            case '1.9.4':
            case '1.9.6':
            case '1.9.8':
                $newVersion = '1.9.0';
                break;
            case '10':
                $newVersion = '10';
                break;
        }
        return $newVersion;

    }

    /**
     * Installation command
     *
     * @param string $version
     * @param $output
     * @return bool
     */
    public function install($version, $output)
    {
        $this->setDoctrineSettings();
        // Fixing the version
        if (!isset($databaseMap[$version])) {
            $version = $this->getVersionToInstall($version);
        }
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

                    if (empty($dbList)) {
                        $output->writeln("<error>No files to load</error>");
                        return false;
                    }

                    $command = $this->getApplication()->find('dbal:import');
                    // Importing sql files.
                    $arguments = array(
                        'command' => 'dbal:import',
                        'file' =>  $dbList
                    );
                    $input = new ArrayInput($arguments);
                    $command->run($input, $output);

                    // Getting extra information about the installation.
                    $output->writeln("<comment>Database </comment><info>$databaseName </info><comment>process ended!</comment>");
                }
            }

            if (isset($sections) && isset($sections['course'])) {
                //@todo fix this
                foreach ($sections['course'] as $courseInfo) {
                    $databaseName = $courseInfo['name'];
                    $output->writeln("Inserting course database in Chamilo: <info>$databaseName</info>");
                    $this->createCourse($this->getHelper('db')->getConnection(), $databaseName);
                }
            }

            if ($this->commandLine) {
                $output->writeln("<comment>Check your installation status with </comment><info>chamilo:status</info>");
            }

            return true;
        } else {
            $output->writeln("<comment>Unknown version: </comment> <info>$version</info>");
        }

        return false;
    }

    private function importSQLFile($file, $output)
    {
        $command = $this->getApplication()->find('dbal:import');

        // Importing sql files.
        $arguments = array(
            'command' => 'dbal:import',
            'file' =>  $file
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        // Getting extra information about the installation.
        $output->writeln("<comment>File loaded </comment><info>$file</info>");
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
     *
     * @param \Doctrine\DBAL\Connection
     * @param string $databaseName
     */
    public function createCourse($connection, $databaseName)
    {
        $params = array(
            'code' => $databaseName,
            'db_name' => $databaseName,
            'course_language' => 'english',
            'title' => $databaseName,
            'visual_code' => $databaseName
        );
        $connection->insert('course', $params);
    }
}
