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
use Symfony\Component\ClassLoader\ClassLoader;
use Symfony\Component\ClassLoader\Psr4ClassLoader;

/**
 * Class InstallCommand
 * @package Chash\Command\Installation
 */
class InstallCommand extends CommonCommand
{
    public $commandLine = true;
    public $oldConfigLocation = false;
    public $path;
    public $version;
    public $silent;
    public $download;
    public $tempFolder;
    public $linuxUser;
    public $linuxGroup;

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this
            ->setName('chash:chamilo_install')
            ->setDescription('Execute a Chamilo installation to a specified version.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null)
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to the chamilo folder')
            ->addOption('download-package', null, InputOption::VALUE_NONE, 'Downloads the chamilo package')
            ->addOption('only-download-package', null, InputOption::VALUE_NONE, 'Only downloads the package')
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function installLegacy(InputInterface $input, OutputInterface $output)
    {
        $version = $this->version;
        $path = $this->path;
        $silent = $this->silent;
        $linuxUser = $this->linuxUser;
        $linuxGroup = $this->linuxGroup;
        $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);

        if (empty($configurationPath)) {
            $output->writeln("<error>There was an error while loading the configuration path (looked for at $configurationPath). Are you sure this is a Chamilo path?</error>");
            $output->writeln("<comment>Try setting up a Chamilo path for example: </comment> <info>chamilo:install 1.11.x /var/www/chamilo</info>");
            $output->writeln("<comment>You can also *download* a Chamilo package adding the --download-package option:</comment>");
            $output->writeln("<info>chamilo:install 1.11.x /var/www/chamilo --download-package</info>");

            return 0;
        }

        if (!is_writable($configurationPath)) {
            $output->writeln("<error>Folder ".$configurationPath." must be writable</error>");
            return 0;
        } else {
            $output->writeln("<comment>Configuration file will be saved here: </comment><info>".$configurationPath."configuration.php </info>");
        }

        $configurationDistExists = false;

        // Try the old one
        if (file_exists($this->getRootSys().'main/install/configuration.dist.php')) {
            $configurationDistExists = true;
        }

        if ($configurationDistExists == false) {
            $output->writeln("<error>configuration.dist.php file nof found</error> <comment>The file must exist in install/configuration.dist.php or app/config/parameter.yml");
            return 0;
        }

        if (file_exists($configurationPath.'configuration.php')) {
            if ($this->commandLine) {
                $output->writeln("<comment>There's a Chamilo portal here:</comment> <info>".$configurationPath."</info>");
                $output->writeln("<comment>You should run <info>chash chash:chamilo_wipe $path </info><comment>if you want to start with a fresh install.</comment>");
                $output->writeln("<comment>You could also manually delete this file:</comment><info> sudo rm ".$configurationPath."configuration.php</info>");
            } else {
                $output->writeln("<comment>There's a Chamilo portal here:</comment> <info>".$configurationPath." </info>");
            }
            return 0;
        }

        if ($this->commandLine) {
            $this->askPortalSettings($input, $output);
            $this->askAdminSettings($input, $output);
            $this->askDatabaseSettings($input, $output);
        }

        $databaseSettings = $this->getDatabaseSettings();
        $connectionToHost = $this->getUserAccessConnectionToHost();
        $connectionToHostConnect = $connectionToHost->connect();

        if ($connectionToHostConnect) {
            $output->writeln(
                sprintf(
                    "<comment>Connection to database %s established. </comment>",
                    $databaseSettings['dbname']
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    "<error>Could not connect to database %s. Please check the database connection parameters.</error>",
                    $databaseSettings['dbname']
                )
            );
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
            $output->writeln(
                sprintf(
                    '<error>Could not create database for connection named <comment>%s</comment></error>',
                    $databaseSettings['dbname']
                )
            );
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return 0;
        }

        if ($connect) {

            $output->writeln(
                sprintf(
                    "<comment>Connection to database '%s' established.</comment>",
                    $databaseSettings['dbname']
                )
            );
            $configurationWasSaved = $this->writeConfiguration($version, $path, $output);

            if ($configurationWasSaved) {

                $absPath = $this->getConfigurationHelper()->getConfigurationPath($path);
                $output->writeln(
                    sprintf(
                        "<comment>Configuration file saved to %s. Proceeding with updating and cleaning stuff.</comment>",
                        $absPath
                    )
                );
                // Installing database.
                $result = $this->processInstallation($databaseSettings, $version, $output);

                if ($result) {
                    // Read configuration file.
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

                    $this->setPortalSettingsInChamilo(
                        $output,
                        $this->getHelper('db')->getConnection()
                    );
                    $this->setAdminSettingsInChamilo(
                        $output,
                        $this->getHelper('db')->getConnection()
                    );

                    // Cleaning temp folders.
                    $command = $this->getApplication()->find('files:clean_temp_folder');
                    $arguments = array(
                        'command' => 'files:clean_temp_folder',
                        '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
                    );

                    $input = new ArrayInput($arguments);
                    $command->run($input, $output);

                    // Generating temp folders.
                    $command = $this->getApplication()->find('files:generate_temp_folders');
                    $arguments = array(
                        'command' => 'files:generate_temp_folders',
                        '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
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
                    //$this->generateConfFiles($output);

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
     * Install Chamilo
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function install(InputInterface $input, OutputInterface $output)
    {
        // Install chamilo in /var/www/html/chamilo-test path

        // Master
        // sudo php /var/www/html/chash/chash.php chash:chamilo_install --download-package --sitename=Chamilo --institution=Chami --institution_url=http://localhost/chamilo-test --encrypt_method=sha1 --permissions_for_new_directories=0777 --permissions_for_new_files=0777 --firstname=John --lastname=Doe --username=admin --password=admin --email=admin@example.com --language=english --phone=666 --driver=pdo_mysql --host=localhost --port=3306 --dbname=chamilo_test --dbuser=root --dbpassword=root master /var/www/html/chamilo-test

        // 1.11.x
        // sudo php /var/www/html/chash/chash.php chash:chamilo_install --download-package --sitename=Chamilo --institution=Chami --institution_url=http://localhost/chamilo-test --encrypt_method=sha1 --permissions_for_new_directories=0777 --permissions_for_new_files=0777 --firstname=John --lastname=Doe --username=admin --password=admin --email=admin@example.com --language=english --phone=666 --driver=pdo_mysql --host=localhost --port=3306 --dbname=chamilo_test --dbuser=root --dbpassword=root  --site_url=http://localhost/chamilo-test 1.11.x /var/www/html/chamilo-test

        // 1.10.x
        // sudo php /var/www/html/chash/chash.php chash:chamilo_install --download-package --sitename=Chamilo --institution=Chami --institution_url=http://localhost/chamilo-test --encrypt_method=sha1 --permissions_for_new_directories=0777 --permissions_for_new_files=0777 --firstname=John --lastname=Doe --username=admin --password=admin --email=admin@example.com --language=english --phone=666 --driver=pdo_mysql --host=localhost --port=3306 --dbname=chamilo_test --dbuser=root --dbpassword=root  --site_url=http://localhost/chamilo-test 1.10.x /var/www/html/chamilo-test

        // 1.9.0
        // sudo rm /var/www/html/chamilo-test/main/inc/conf/configuration.php

        /*
            sudo rm -R /var/www/html/chamilo-test/
            sudo php /var/www/html/chash/chash.php chash:chamilo_install --download-package --sitename=Chamilo --institution=Chami --institution_url=http://localhost/chamilo-test --encrypt_method=sha1 --permissions_for_new_directories=0777 --permissions_for_new_files=0777 --firstname=John --lastname=Doe --username=admin --password=admin --email=admin@example.com --language=english --phone=666 --driver=pdo_mysql --host=localhost --port=3306 --dbname=chamilo_test --dbuser=root --dbpassword=root  --site_url=http://localhost/chamilo-test 1.9.0 /var/www/html/chamilo-test
            cd /var/www/html/chamilo-test/
        */

        $this->askDatabaseSettings($input, $output);

        if (empty($this->databaseSettings)) {
            $output->writeln("<comment>Cannot get database settings. </comment>");
            return false;
        } else {
            var_dump($this->databaseSettings);
        }

        if ($this->commandLine) {
            $connectionToDatabase = $this->getUserAccessConnectionToDatabase();
            $connectionToDatabase->connect();
            $version = $this->version;

            // Installing database.
            $result = $this->processInstallation($this->databaseSettings, $version, $output);
            if ($result) {
                $path = $this->path;
                $silent = $this->silent;
                $linuxUser = $this->linuxUser;
                $linuxGroup = $this->linuxGroup;

                $configurationWasSaved = $this->writeConfiguration($version, $path, $output);
                if ($configurationWasSaved) {
                    $this->askPortalSettings($input, $output);
                    $this->setDoctrineSettings($this->getHelperSet());
                    $this->setPortalSettingsInChamilo(
                        $output,
                        $connectionToDatabase
                    );
                }
            }
        }
    }

    /**
     * Ask for DB settings.
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function askDatabaseSettings(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $filledParams = $this->getParamsFromOptions(
            $input,
            $this->getDatabaseSettingsParams()
        );
        $params = $this->getDatabaseSettingsParams();
        $total = count($params);
        $output->writeln(
            "<comment>Database settings: (" . $total . ")</comment>"
        );
        $databaseSettings = array();
        $counter = 1;
        foreach ($params as $key => $value) {
            if (!isset($filledParams[$key])) {
                if (!$input->isInteractive() && (in_array($key, array('dbpassword', 'port', 'host', 'driver')))) {
                    // db password may be empty, so if not provided and the
                    // --no-interaction mode was configured, forget about it
                    switch ($key) {
                        case 'dbpassword':
                            $databaseSettings[$key] = null;
                            $output->writeln(
                                "($counter/$total) <comment>Option: $key was not provided. Using default value null (empty password)</comment>"
                            );
                            break;
                        case 'host':
                            $databaseSettings[$key] = 'localhost';
                            $output->writeln(
                                "($counter/$total) <comment>Option: $key was not provided. Using default value " . $databaseSettings[$key] . "</comment>"
                            );
                            break;
                        case 'port':
                            $databaseSettings[$key] = '3306';
                            $output->writeln(
                                "($counter/$total) <comment>Option: $key was not provided. Using default value " . $databaseSettings[$key] . "</comment>"
                            );
                            break;
                        case 'driver':
                            $databaseSettings[$key] = 'pdo_mysql';
                            $output->writeln(
                                "($counter/$total) <comment>Option: $key was not provided. Using default value " . $databaseSettings[$key] . "</comment>"
                            );
                            break;
                    }
                    $counter++;
                } else {
                    $data = $dialog->ask(
                        $output,
                        "($counter/$total) Please enter the value of the $key (" . $value['attributes']['data'] . "): ",
                        $value['attributes']['data']
                    );
                    $counter++;
                    $databaseSettings[$key] = $data;
                }
            } else {
                $output->writeln(
                    "($counter/$total) <comment>Option: $key = '" . $filledParams[$key] . "' was added as an option. </comment>"
                );
                $counter++;
                $databaseSettings[$key] = $filledParams[$key];
            }
        }
        $this->setDatabaseSettings($databaseSettings);
    }

    /**
     * Asks for admin settings.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function askAdminSettings(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        // Ask for admin settings

        $filledParams = $this->getParamsFromOptions(
            $input,
            $this->getAdminSettingsParams()
        );

        $params = $this->getAdminSettingsParams();
        $total = count($params);
        $output->writeln(
            "<comment>Admin settings: (" . $total . ")</comment>"
        );
        $adminSettings = array();
        $counter = 1;

        foreach ($params as $key => $value) {
            if (!isset($filledParams[$key])) {
                $data = $dialog->ask(
                    $output,
                    "($counter/$total) Please enter the value of the $key (" . $value['attributes']['data'] . "): ",
                    $value['attributes']['data']
                );
                $counter++;
                $adminSettings[$key] = $data;
            } else {
                $output->writeln(
                    "($counter/$total) <comment>Option: $key = '" . $filledParams[$key] . "' was added as an option. </comment>"
                );
                $counter++;
                $adminSettings[$key] = $filledParams[$key];
            }
        }

        $this->setAdminSettings($adminSettings);
    }

    /**
     * Ask for portal settings.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function askPortalSettings(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        // Ask for portal settings.
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
    }


    /**
     * Setting common parameters.
     * @param InputInterface $input
     */
    public function settingParameters(InputInterface $input)
    {
        if (PHP_SAPI != 'cli') {
            $this->commandLine = false;
        }

        // Arguments
        $this->path = $input->getArgument('path');
        $this->version = $input->getArgument('version');
        $this->silent = $input->getOption('silent') == true;
        $this->download = $input->getOption('download-package');
        $this->tempFolder = $input->getOption('temp-folder');
        $this->linuxUser = $input->getOption('linux-user');
        $this->linuxGroup = $input->getOption('linux-group');

        // Getting the new config folder.
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($this->path);

        // @todo move this in the helper
        if ($configurationPath == false) {
            // Seems an old installation!
            $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($this->path);

            if (strpos($configurationPath, 'app/config') === false) {
                // Version 1.9.x
                $this->setRootSys(
                    realpath($configurationPath.'/../../../').'/'
                );
                $this->oldConfigLocation = true;
            } else {
                // Version 1.10.x
                // Legacy but with new location app/config
                $this->setRootSys(realpath($configurationPath.'/../../').'/');
                $this->oldConfigLocation = true;
            }
        } else {
            // Chamilo v2/v1.x installation.
            /*$this->setRootSys(realpath($configurationPath.'/../').'/');
            $this->oldConfigLocation = false;*/
            $this->setRootSys(realpath($configurationPath.'/../../').'/');
            $this->oldConfigLocation = true;
        }

        $this->getConfigurationHelper()->setIsLegacy($this->oldConfigLocation);
        $this->setConfigurationPath($configurationPath);
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
        // Setting configuration helper.
        $this->getApplication()->getHelperSet()->set(
            new \Chash\Helpers\ConfigurationHelper(),
            'configuration'
        );

        $this->settingParameters($input);

        $version = $this->version;
        $download = $this->download;
        $tempFolder = $this->tempFolder;
        $path = $this->path;

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

            $this->settingParameters($input);
            if ($input->getOption('only-download-package')) {
                return 0;
            }
        }

        if ($this->commandLine) {
            $title = "Welcome to the Chamilo installation process.";
        } else {
            $title = "Chamilo installation process.";
        }

        $this->writeCommandHeader($output, $title);

        $versionInfo = $this->availableVersions()[$version];
        if (isset($versionInfo['parent'])) {
            $parent = $versionInfo['parent'];
            if (in_array($parent, ['1.9.0', '1.10.0', '1.11.0'])) {
                $isLegacy = true;
            } else {
                $isLegacy = false;
            }
        } else {
            $output->writeln("<comment>Chamilo $version doesnt have a parent</comment>");
            return false;
        }

        //$isLegacy = $this->getConfigurationHelper()->isLegacy();
        if ($isLegacy) {
            $this->installLegacy($input, $output);
        } else {
            $this->install($input, $output);
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
            case '1.9.10':
            case '1.9.10.2':
            case '1.9.x':
                $newVersion = '1.9.0';
                break;
            case '1.10':
            case '1.10.0':
            case '1.10.x':
                $newVersion = '1.10.0';
                break;
            case '1.11.x':
                $newVersion = '1.11.0';
                break;
            case '2':
            case '2.0':
            case 'master':
                $newVersion = '2.0';
                break;
        }

        return $newVersion;

    }

    /**
     * Installation command
     *
     * @param array $databaseSettings
     * @param string $version
     * @param $output
     * @return bool
     */
    public function processInstallation($databaseSettings, $version, $output)
    {
        $em = $this->setDoctrineSettings($this->getHelperSet());

        $sqlFolder = $this->getInstallationPath($version);
        $databaseMap = $this->getDatabaseMap();
        // Fixing the version
        if (!isset($databaseMap[$version])) {
            $version = $this->getVersionToInstall($version);
        }

        if (isset($databaseMap[$version])) {
            $dbInfo = $databaseMap[$version];
            $output->writeln("<comment>Starting creation of database version </comment><info>$version... </info>");
            $sections = $dbInfo['section'];

            $sectionsCount = 0;
            foreach ($sections as $sectionData) {
                if (is_array($sectionData)) {
                    foreach ($sectionData as $dbInfo) {
                        $databaseName = $dbInfo['name'];
                        $dbList = $dbInfo['sql'];

                        if (!empty($dbList)) {
                            $output->writeln(
                                "<comment>Creating database</comment> <info>$databaseName ... </info>"
                            );

                            if (empty($dbList)) {
                                $output->writeln(
                                    "<error>No files to load.</error>"
                                );
                                continue;
                            } else {

                                // Fixing db list
                                foreach ($dbList as &$db) {
                                    $db = $sqlFolder.$db;
                                }

                                $command = $this->getApplication()->find(
                                    'dbal:import'
                                );
                                // Importing sql files.
                                $arguments = array(
                                    'command' => 'dbal:import',
                                    'file' => $dbList
                                );
                                $input = new ArrayInput($arguments);
                                $command->run($input, $output);

                                // Getting extra information about the installation.
                                $output->writeln(
                                    "<comment>Database </comment><info>$databaseName </info><comment>setup process terminated successfully!</comment>"
                                );
                            }
                            $sectionsCount++;
                        }
                    }
                }
            }

            // Run
            switch ($version) {
                case '2.0':
                case 'master':
                    require_once $this->getRootSys().'/main/inc/lib/database.constants.inc.php';
                    require_once $this->getRootSys().'/main/inc/lib/api.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/text.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/display.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/database.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/custom_pages.class.php';
                    require_once $this->getRootSys().'/main/install/install.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/hook/interfaces/base/HookEventInterface.php';
                    require_once $this->getRootSys().'/main/inc/lib/hook/interfaces/HookCreateUserEventInterface.php';
                    require_once $this->getRootSys().'/main/inc/lib/hook/interfaces/base/HookManagementInterface.php';
                    require_once $this->getRootSys().'/main/inc/lib/hook/HookEvent.php';
                    require_once $this->getRootSys().'/main/inc/lib/hook/HookCreateUser.php';
                    require_once $this->getRootSys().'/main/inc/lib/hook/HookManagement.php';
                    require_once $this->getRootSys().'/main/inc/lib/model.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/events.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/extra_field.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/extra_field_value.lib.php';
                    require_once $this->getRootSys().'/main/inc/lib/urlmanager.lib.php';
                    require_once $this->getRootSys().'/vendor/autoload.php';
                    $encoder = $this->getRootSys().'/src/Chamilo/UserBundle/Security/Encoder.php';
                    if (file_exists($encoder)) {
                        require_once $encoder;
                    }

                    require_once $this->getRootSys().'/main/inc/lib/usermanager.lib.php';


                    $newInstallationPath = $this->getRootSys();
                    $chashPath = __DIR__.'/../../../../';


                      // Registering Constraints
                    AnnotationRegistry::registerAutoloadNamespace(
                        'APY\DataGridBundle\Grid\Mapping',
                        $newInstallationPath.'vendor/apy/datagrid-bundle/Grid/Mapping'
                    );

                    AnnotationRegistry::registerFile(
                        $newInstallationPath.'vendor/apy/datagrid-bundle/Grid/Mapping/Column.php'
                    );

                    $database = new \Database();
                    $database::$utcDateTimeClass = 'Chash\DoctrineExtensions\DBAL\Types\UTCDateTimeType';

                    $database->connect($databaseSettings, $chashPath, $newInstallationPath);
                    $manager = $database->getManager();

                    $metadataList = $manager->getMetadataFactory()->getAllMetadata();

                    $output->writeln("<comment>Creating database structure</comment>");
                    $manager->getConnection()->getSchemaManager()->createSchema();

                    // Create database schema
                    $tool = new \Doctrine\ORM\Tools\SchemaTool($manager);
                    $tool->createSchema($metadataList);
                    break;
            }

            if (isset($sections) && isset($sections['course'])) {
                //@todo fix this
                foreach ($sections['course'] as $courseInfo) {
                    $databaseName = $courseInfo['name'];
                    $output->writeln("Inserting course database in Chamilo: <info>$databaseName</info>");
                    $this->createCourse($this->getHelper('db')->getConnection(), $databaseName);
                    $sectionsCount ++;
                }
            }

            // Special migration for chamilo v 1.10
            if (isset($sections) && isset($sections['migrations'])) {
                $sectionsCount = 1;
                require_once $this->getRootSys().'/main/inc/lib/database.constants.inc.php';
                require_once $this->getRootSys().'/main/inc/lib/system/session.class.php';
                require_once $this->getRootSys().'/main/inc/lib/chamilo_session.class.php';
                require_once $this->getRootSys().'/main/inc/lib/api.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/text.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/display.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/database.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/custom_pages.class.php';
                require_once $this->getRootSys().'/main/install/install.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/hook/interfaces/base/HookEventInterface.php';
                require_once $this->getRootSys().'/main/inc/lib/hook/interfaces/HookCreateUserEventInterface.php';
                require_once $this->getRootSys().'/main/inc/lib/hook/interfaces/base/HookManagementInterface.php';
                require_once $this->getRootSys().'/main/inc/lib/hook/HookEvent.php';
                require_once $this->getRootSys().'/main/inc/lib/hook/HookCreateUser.php';
                require_once $this->getRootSys().'/main/inc/lib/hook/HookManagement.php';
                require_once $this->getRootSys().'/main/inc/lib/model.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/events.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/extra_field.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/extra_field_value.lib.php';
                require_once $this->getRootSys().'/main/inc/lib/urlmanager.lib.php';
                require_once $this->getRootSys().'/vendor/autoload.php';
                $encoder = $this->getRootSys().'/src/Chamilo/UserBundle/Security/Encoder.php';
                if (file_exists($encoder)) {
                    require_once $encoder;
                }
                require_once $this->getRootSys().'/main/inc/lib/usermanager.lib.php';

                $newInstallationPath = $this->getRootSys();
                $chashPath = __DIR__.'/../../../../';

                $database = new \Database();
                $database::$utcDateTimeClass = 'Chash\DoctrineExtensions\DBAL\Types\UTCDateTimeType';

                $database->connect($databaseSettings, $chashPath, $newInstallationPath);
                $manager = $database->getManager();

                $metadataList = $manager->getMetadataFactory()->getAllMetadata();

                $output->writeln("<comment>Creating database structure</comment>");
                $manager->getConnection()->getSchemaManager()->createSchema();

                // Create database schema
                $tool = new \Doctrine\ORM\Tools\SchemaTool($manager);
                $tool->createSchema($metadataList);

                $portalSettings = $this->getPortalSettings();
                $adminSettings = $this->getAdminSettings();

                \finishInstallation(
                    $manager,
                    $newInstallationPath,
                    $portalSettings['encrypt_method'],
                    $adminSettings['password'],
                    $adminSettings['lastname'],
                    $adminSettings['firstname'],
                    $adminSettings['username'],
                    $adminSettings['email'],
                    $adminSettings['phone'],
                    $adminSettings['language'],
                    $portalSettings['institution'],
                    $portalSettings['institution_url'],
                    $portalSettings['sitename'],
                    false, //$allowSelfReg,
                    false //$allowSelfRegProf
                );

                $output->writeln("<comment>Remember to run composer install</comment>");
            }

            if ($sectionsCount == 0) {
                $output->writeln("<comment>No database section found for creation</comment>");
            }

            $output->writeln("<comment>Check your installation status with </comment><info>chamilo:status</info>");

            return true;
        } else {
            $output->writeln("<comment>Unknown version: </comment> <info>$version</info>");
        }

        return false;
    }

    /**
     * @param $file
     * @param $output
     * @throws \Exception
     */
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
     *                  0 when a new database is impossible to be created,
     * then the single/multiple database configuration is impossible too
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
        $settings = $this->getDatabaseSettings();
        $settings['dbname'] = null;
        $conn = \Doctrine\DBAL\DriverManager::getConnection(
            $settings,
            $config
        );

        return $conn;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getUserAccessConnectionToDatabase()
    {
        $config = new \Doctrine\DBAL\Configuration();
        $settings = $this->getDatabaseSettings();

        $conn = \Doctrine\DBAL\DriverManager::getConnection(
            $settings,
            $config
        );

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
