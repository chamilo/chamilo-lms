<?php

namespace Chash\Command\Installation;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class UpgradeCommand
 * @package Chash\Command\Installation
 */
class UpgradeCommand extends CommonCommand
{
    public $queryList;
    public $databaseList;
    public $commandLine = true;

    /**
     * Get connection
     * @return\Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->getHelper('db')->getConnection();
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('chamilo:upgrade')
            ->setDescription('Execute a chamilo migration to a specified version or the latest available version')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to', null)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the chamilo folder')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run')
            ->addOption('update-installation', null, InputOption::VALUE_OPTIONAL, 'Updates the portal with the current zip file. http:// or /var/www/file.zip')
            ->addOption('temp-folder', null, InputOption::VALUE_OPTIONAL, 'The temp folder', '/tmp')
            ->addOption('download-package', null, InputOption::VALUE_OPTIONAL, 'Download the chamilo package', 'true')
            ->addOption('linux-user', null, InputOption::VALUE_OPTIONAL, 'user', 'www-data')
            ->addOption('linux-group', null, InputOption::VALUE_OPTIONAL, 'group', 'www-data')
            ->addOption('custom-package', null, InputOption::VALUE_OPTIONAL, 'Custom zip package location.', '')
            ->addOption('remove-unused-table', null, InputOption::VALUE_NONE, 'Remove unused tables.')
        ;
            //->addOption('force', null, InputOption::VALUE_NONE, 'Force the update. Only for tests');
    }

    /**
     * Executes a command via CLI
     *
     * @param   InputInterface $input
     * @param   OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();

        if (PHP_SAPI != 'cli') {
            $this->commandLine = false;
        }

        $removeUnusedTables = $input->getOption('remove-unused-table');

        // Setting up Chash
        $command = $this->getApplication()->find('chash:setup');

        // User options
        $linuxUser = $input->getOption('linux-user');
        $linuxGroup = $input->getOption('linux-group');

        $arguments = array(
            'command' => 'chash:setup'
        );

        $inputSetup = new ArrayInput($arguments);
        $command->run($inputSetup, $output);
        $migrationFile = $command->getMigrationFile();

        if (empty($migrationFile) || !file_exists($migrationFile)) {
            $output->writeln("<error>Set the --migration-yml-path and --migration-class-path manually.</error>");
            return 0;
        }

        // Checking Resources/Database dir. Getting the Resources/Database/1.8.7/db_main.sql.
        $testFolder = $this->getInstallationFolder().'1.8.7/db_main.sql';
        $installationFolder = $this->getInstallationFolder();

        if (!file_exists($testFolder)) {
            $output->writeln("<error>The migration directory was not detected: </error><info>$installationFolder</info>");
            return 0;
        } else {
            $output->writeln("<comment>Reading migrations from directory: </comment><info>$installationFolder</info>");
        }

        $this->setMigrationConfigurationFile($command->getMigrationFile());

        // Arguments and options
        $version = $originalVersion = $input->getArgument('version');
        $path = $input->getOption('path');
        $dryRun = $input->getOption('dry-run');
        $silent = !$input->isInteractive();
        $tempFolder = $input->getOption('temp-folder');
        $downloadPackage = $input->getOption('download-package') == 'true' ? true : false;

        $customPackage = $input->getOption('custom-package');
        if (!empty($customPackage)) {
            $downloadPackage = false;
        }

        $updateInstallation = $input->getOption('update-installation');

        // Setting the configuration path and configuration array
        $_configuration = $this->getConfigurationHelper()->getConfiguration($path);

        if (empty($_configuration)) {
            $output->writeln("<comment>Chamilo is not installed here! You may add a path as an option:</comment>");
            $output->writeln("<comment>For example: </comment><info>chamilo:upgrade 1.9.0 --path=/var/www/chamilo</info>");
            return 0;
        }

        $this->setConfigurationArray($_configuration);
        $this->getConfigurationHelper()->setConfiguration($_configuration);
        $this->setRootSysDependingConfigurationPath($path);

        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);

        // Checking configuration file.
        if (!is_writable($configurationPath)) {
            $output->writeln("<comment>Folder ".$configurationPath." must have writeable permissions</comment>");
            return 0;
        }

        $this->setConfigurationPath($configurationPath);

        // $_configuration['password_encryption'] must exists

        if (isset($_configuration['password_encryption'])) {
            $output->writeln("<comment> \$_configuration[password_encryption] value found: </comment><info>".$_configuration['password_encryption']."</info>");
        } else {
            $output->writeln("<error>\$_configuration['password_encryption'] not found. The key 'password_encryption' or the variable '\$userPasswordCrypted' must exist in the configuration.php file </error>");
            return 0;
        }

        // In order to use Doctrine migrations

        // Setting configuration variable in order to get the doctrine version:
        //$input->setOption('configuration', $this->getMigrationConfigurationFile());
        //$configuration = $this->getMigrationConfiguration($input, $output);

        // Doctrine migrations version
        //$doctrineVersion = $configuration->getCurrentVersion();
        // Moves files from main/inc/conf to config/

        $doctrineVersion = null;

        // Getting supported version number list
        $versionNameList = $this->getVersionNumberList();
        $minVersion = $this->getMinVersionSupportedByInstall();
        $versionList = $this->availableVersions();

        // Checking version.
        if ($version != 'master') {
            if (!in_array($version, $versionNameList)) {
                $output->writeln("<comment>Version '$version' is not available.</comment>");
                $output->writeln("<comment>Available versions: </comment><info>".implode(', ', $versionNameList)."</info>");
                return 0;
            }
        }

        $currentVersion = null;

        // Checking system_version.

        if (!isset($_configuration['system_version']) || empty($_configuration['system_version'])) {
            $output->writeln("<comment>There is something wrong in your Chamilo installation. Check it with the chamilo:status command</comment>");
            return 0;
        }

        if (version_compare($_configuration['system_version'], $minVersion, '<')) {
            $output->writeln("<comment>Your Chamilo version is not supported! The minimun version is: </comment><info>$minVersion</info>");
            $output->writeln("<comment>You want to upgrade from <info>".$_configuration['system_version']."</info> <comment>to</comment> <info>$minVersion</info>");
            return 0;
        }

        if ($version != 'master') {
            if (version_compare($version, $_configuration['system_version'], '>')) {
                $currentVersion = $_configuration['system_version'];
            } else {
                $output->writeln("<comment>Please provide a version greater than </comment><info>".$_configuration['system_version']."</info>");
                $output->writeln("<comment>You selected version: </comment><info>$version</info>");
                $output->writeln("<comment>You can also check your installation status with </comment><info>chamilo:status");
                return 0;
            }
        } else {
            $currentVersion = $_configuration['system_version'];
            $version = $this->getLatestVersion();
        }

        $versionInfo = $this->getAvailableVersionInfo($version);

        if (empty($versionInfo)) {
            $output->writeln("<comment>The current version ($version) is not supported</comment>");
            return 0;
        }

        if (isset($versionInfo['hook_to_doctrine_version']) && isset($doctrineVersion)) {
            if ($doctrineVersion == $versionInfo['hook_to_doctrine_version']) {
                $output->writeln("<comment>You already have the latest version. Nothing to update! Doctrine version $doctrineVersion</comment>");
                return 0;
            }
        }

        if (isset($versionInfo['parent']) && !empty($versionInfo['parent'])) {
            $versionInfoParent = $this->getAvailableVersionInfo($versionInfo['parent']);
            if ($doctrineVersion == $versionInfoParent['hook_to_doctrine_version']) {
                $output->writeln("<comment>You already have the latest version. Nothing to update! Doctrine version $doctrineVersion</comment>");
                return 0;
            }
        }

        if ($dryRun == false) {
            if ($downloadPackage) {
                $output->writeln("<comment>Downloading package ...</comment>");
                $chamiloLocationPath = $this->getPackage($output, $originalVersion, $updateInstallation, $tempFolder);
                if (empty($chamiloLocationPath)) {
                    $output->writeln("<comment>Chash was not able to unzip the downloaded package for version: $originalVersion</comment>");
                    return 0;
                }
            } else {
                if (!empty($customPackage)) {
                    $chamiloLocationPath = $customPackage;
                }
            }
        }

        $this->writeCommandHeader($output, 'Welcome to the Chamilo upgrade process!');

        if ($dryRun == false) {
            if ($downloadPackage) {
                $output->writeln("<comment>When the installation process finishes, the files located here:<comment>");
                $output->writeln("<info>$chamiloLocationPath</info>");
                $output->writeln("<comment>will be copied to your portal here: </comment><info>".$this->getRootSys()."</info>");
            }

            if ($removeUnusedTables) {
                if (version_compare($currentVersion, '1.9.0', '<')) {
                    $onlyPrefix = $this->getTablePrefix($_configuration);
                    if (!empty($onlyPrefix)) {
                        $output->writeln("<comment>--remove-unused-table option is on. Unused tables will be removed.<comment>");
                        $tablesToDelete = "SHOW TABLES LIKE '".$onlyPrefix."%';";
                        $output->writeln("<comment>All tables that match this query will be deleted:</comment> <info>$tablesToDelete</info>");
                    } else {
                        $output->writeln("<comment>--remove-unused-table option is on. But any table will be removed.<comment>");
                    }
                } else {
                    $output->writeln("<comment>--remove-unused-table option is on. This does not affect this $originalVersion version.<comment>");
                }
            }
        } else {
            $output->writeln("<comment>When the installation process finishes, PHP files are not going to be updated (--dry-run is on).</comment>");
        }

        if ($silent == false) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>Are you sure you want to upgrade the Chamilo located here?</question> <info>'.$_configuration['root_sys'].'</info> (y/N)',
                false
            )
            ) {
                return;
            }

            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>Are you sure you want to upgrade from version</question> <info>'.$_configuration['system_version'].'</info> <comment>to version</comment> <info>'.$version.'</info> (y/N)',
                false
            )
            ) {
                return;
            }
        }

        $output->writeln('<comment>Migrating from Chamilo version: </comment><info>'.$_configuration['system_version'].'</info><comment> to version <info>'.$version);
        $output->writeln('<comment>Starting upgrade for Chamilo, reading configuration file located here: </comment><info>'.$configurationPath.'configuration.php</info>');

         // Getting configuration file.
        $_configuration = $this->getHelper('configuration')->getConfiguration($path);

        // Upgrade always from a mysql driver
        $databaseSettings = array(
            'driver' => 'pdo_mysql',
            'host' => $_configuration['db_host'],
            'dbname' => $_configuration['main_database'],
            'user' => $_configuration['db_user'],
            'password' => $_configuration['db_password'],
        );

        // Setting DB access.
        $this->setDatabaseSettings($databaseSettings);

        $extraDatabaseSettings = array(
            'single_database'=> isset($_configuration['single_database']) ? $_configuration['single_database'] : false,
            'table_prefix'=> isset($_configuration['table_prefix']) ? $_configuration['table_prefix'] : null,
            'db_glue' => isset($_configuration['db_glue']) ? $_configuration['db_glue'] : null,
            'db_prefix' => isset($_configuration['db_prefix']) ? $_configuration['db_prefix'] : null,
        );

        $this->setExtraDatabaseSettings($extraDatabaseSettings);
        $this->setDoctrineSettings();
        $conn = $this->getConnection();
        $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        if ($conn) {
            $output->writeln("<comment>Connection to the database established.</comment>");
        } else {
            $output->writeln("<comment>Can't connect to the DB with user:</comment><info>".$_configuration['db_user'])."</info>";
            return 0;
        }

        // Get course list
        $query = "SELECT * FROM course";
        $result = $conn->executeQuery($query);
        $courseList = $result->fetchAll();

        $oldVersion = $currentVersion;
        foreach ($versionList as $versionItem => $versionInfo) {
            if (version_compare($versionItem, $currentVersion, '>') && version_compare($versionItem, $version, '<=')) {
                $output->writeln("----------------------------------------------------------------");
                $output->writeln("<comment>Starting migration from version: </comment><info>$oldVersion</info><comment> to version </comment><info>$versionItem ");
                $output->writeln("");

                if (isset($versionInfo['require_update']) && $versionInfo['require_update'] == true) {
                    // Greater than my current version.
                    $this->startMigration($courseList, $path, $versionItem, $dryRun, $output, $removeUnusedTables, $input);
                    $oldVersion = $versionItem;
                    $output->writeln("----------------------------------------------------------------");
                } else {
                    $output->writeln("<comment>Skip migration for version: </comment><info>'$versionItem'</info>");
                }
            }
        }

        // Update chamilo files.
        if ($dryRun == false) {
            $this->copyPackageIntoSystem($output, $chamiloLocationPath, null);
            if ($version == '1.10.0') {
                $this->removeUnUsedFiles($output, $path);
                $this->copyConfigFilesToNewLocation($output);
            }
        }

        // Generating temp folders.
        $command = $this->getApplication()->find('files:generate_temp_folders');
        $arguments = array(
            'command' => 'files:generate_temp_folders',
            '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
            '--dry-run' => $dryRun
        );

        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        // Fixing permissions.
        $command = $this->getApplication()->find('files:set_permissions_after_install');
        $arguments = array(
            'command' => 'files:set_permissions_after_install',
            '--conf' => $this->getConfigurationHelper()->getConfigurationFilePath($path),
            '--linux-user' => $linuxUser,
            '--linux-group' => $linuxGroup,
            '--dry-run' => $dryRun
        );

        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        // Update configuration file new system_version
        $newParams = array('system_version' => $version);
        $this->updateConfiguration($output, $dryRun, $newParams);

        $output->writeln("<comment>Hurray!!! You just finished this migration. To check the current status of your platform, run </comment><info>chamilo:status</info>");
        $endTime = time();
        $totalTimeInMinutes = round(($endTime - $startTime)/60, 2);
        $output->writeln("<comment>The script took $totalTimeInMinutes minutes to execute.</comment>");
    }

    /**
     * Starts a migration
     *
     * @param array $courseList
     * @param string $path
     * @param string $toVersion
     * @param bool $dryRun
     * @param OutputInterface $output
     * @param bool $removeUnusedTables
     * @param InputInterface $mainInput
     *
     * @return bool
     * @throws \Exception
     */
    public function startMigration(
        $courseList,
        $path,
        $toVersion,
        $dryRun,
        OutputInterface $output,
        $removeUnusedTables = false,
        InputInterface $mainInput
    ) {
        // Cleaning query list.
        $this->queryList = array();

        // Main DB connection.
        $conn = $this->getConnection();

        $_configuration = $this->getHelper('configuration')->getConfiguration($path);

        $versionInfo = $this->getAvailableVersionInfo($toVersion);
        $installPath = $this->getInstallationFolder().$toVersion.'/';

        $output->writeln("<comment>Reading installation directory for version $toVersion: <info>'$installPath'</info></comment>");

        // Filling sqlList array with "pre" db changes.
        if (isset($versionInfo['pre']) && !empty($versionInfo['pre'])) {
            $sqlToInstall = $installPath.$versionInfo['pre'];
            $this->fillQueryList($sqlToInstall, $output, 'pre');

            // Processing sql query list depending of the section (course, main, user).
            $this->processQueryList($courseList, $output, $path, $toVersion, $dryRun, 'pre');
        }

        try {
            // Doctrine migrations:

            $output->writeln('');
            $output->writeln("<comment>You have to select 'yes' for the 'Chamilo Migrations'<comment>");

            $command = $this->getApplication()->find('migrations:migrate');
            $arguments = array(
                'command' => 'migrations:migrate',
                'version' => $versionInfo['hook_to_doctrine_version'],
                '--configuration' => $this->getMigrationConfigurationFile(),
                '--dry-run' => $dryRun
            );

            $output->writeln("<comment>Executing migrations:migrate ".$versionInfo['hook_to_doctrine_version']." --configuration=".$this->getMigrationConfigurationFile()."<comment>");
            $input = new ArrayInput($arguments);

            if ($this->commandLine == false) {
                $input->setInteractive(false);
            } else {
                $input->setInteractive($mainInput->isInteractive());
            }

            $command->run($input, $output);

            $output->writeln("<comment>Migration ended successfully</comment>");

        } catch (\Exception $e) {
            // Reverting changes

            /*$output->writeln("<comment>Reverting changes ... <comment>");

            $command = $this->getApplication()->find('migrations:execute');
            $arguments = array(
                'command' => 'migrations:execute',
                'version' => $versionInfo['hook_to_doctrine_version'],
                '--down' => true,
                '--configuration' => $this->getMigrationConfigurationFile(),
                '--dry-run' => $dryRun
            );

            $output->writeln("<comment>Executing migrations:migrate ".($versionInfo['hook_to_doctrine_version'] - 1)."<comment>");
            $input = new ArrayInput($arguments);
            if ($this->commandLine == false) {
                $input->setInteractive(false);
            }
            $command->run($input, $output);
            */
            $output->write(sprintf('<error>Migration failed. Error %s</error>', $e->getMessage()));

            throw $e;
        }

        // Processing "db" changes.
        if (isset($versionInfo['update_db']) && !empty($versionInfo['update_db'])) {
            $sqlToInstall = $installPath.$versionInfo['update_db'];
            if (is_file($sqlToInstall) && file_exists($sqlToInstall)) {

                if ($dryRun) {
                    $output->writeln("<comment>File to be executed but not fired because of dry-run option: <info>'$sqlToInstall'</info>");
                } else {
                    $output->writeln("<comment>Executing update db: <info>'$sqlToInstall'</info>");
                }

                require $sqlToInstall;

                if (!empty($update)) {
                    $update($_configuration, $conn, $courseList, $dryRun, $output, $this, $removeUnusedTables);
                }
            } else {
                $output->writeln(sprintf("File doesn't exist: '<info>%s</info>'", $sqlToInstall));
            }
        }

        // Processing "update file" changes.
        if (isset($versionInfo['update_files']) && !empty($versionInfo['update_files'])) {
            $sqlToInstall = $installPath.$versionInfo['update_files'];
            if (is_file($sqlToInstall) && file_exists($sqlToInstall)) {
                if ($dryRun) {
                    $output->writeln("<comment>Files to be executed but dry-run is on: <info>'$sqlToInstall'</info>");
                } else {
                    $output->writeln("<comment>Executing update files: <info>'$sqlToInstall'</info>");

                    require $sqlToInstall;

                    if (!empty($updateFiles)) {
                        $updateFiles($_configuration, $conn, $courseList, $dryRun, $output, $this);
                    }
                }
            } else {
                $output->writeln(sprintf("File doesn't exist: '<info>%s</info>'", $sqlToInstall));
            }
        }

        // Filling sqlList array with "post" db changes.
        if (isset($versionInfo['post']) && !empty($versionInfo['post'])) {
            $sqlToInstall = $installPath.$versionInfo['post'];
            $this->fillQueryList($sqlToInstall, $output, 'post');
            // Processing sql query list depending of the section.
            $this->processQueryList($courseList, $output, $path, $toVersion, $dryRun, 'post');
        }

        return false;
    }

    /**
     * @return array
     */
    public function getMigrationTypes()
    {
        return array(
            'pre',
            'post'
        );
    }

    /**
     *
     * Process the queryList array and executes queries to the correct section (main, user, course, etc)
     *
     * @param array $courseList
     * @param OutputInterface $output
     * @param $path
     * @param $version
     * @param $dryRun
     * @param $type
     * @return bool
     * @throws \Exception
     */
    public function processQueryList($courseList, $output, $path, $version, $dryRun, $type)
    {
        $databases = $this->getDatabaseList($output, $courseList, $path, $version, $type);
        $this->setConnections($version, $path, $databases);

        foreach ($databases as $section => &$dbList) {
            foreach ($dbList as &$dbInfo) {

                $output->writeln("");
                $output->writeln("<comment>Loading section:</comment> <info>$section</info> <comment>using database key</comment> <info>".$dbInfo['database']."</info>");
                $output->writeln("--------------------------");

                if ($dbInfo['status'] == 'complete') {
                    $output->writeln("<comment>Database already updated.</comment>");
                    continue;
                }

                if (isset($this->queryList[$type]) &&
                    isset($this->queryList[$type][$section]) &&
                    !empty($this->queryList[$type][$section])
                ) {
                    $queryList = $this->queryList[$type][$section];
                    $output->writeln("<comment>Loading queries list: '$type' - '$section'</comment>");

                    if (!empty($queryList)) {

                        try {
                            $lines = 0;

                            /** @var \Doctrine\DBAL\Connection $conn */
                            $conn = $this->getHelper($dbInfo['database'])->getConnection();
                            $output->writeln("<comment>Executing queries in DB:</comment> <info>".$conn->getDatabase()."</info>");

                            $conn->beginTransaction();

                            foreach ($queryList as $query) {
                                // Add a prefix.

                                if ($section == 'course') {
                                    $query = str_replace('{prefix}', $dbInfo['prefix'], $query);
                                }

                                if ($dryRun) {
                                    $output->writeln($query);
                                } else {
                                    $output->writeln('     <comment>-></comment> ' . $query);
                                    $conn->executeQuery($query);
                                    //$conn->exec($query);
                                }
                                $lines++;
                            }

                            if (!$dryRun) {
                                if ($conn->isTransactionActive()) {
                                    $conn->commit();
                                }
                                $output->writeln(sprintf('%d statements executed!', $lines) . PHP_EOL);
                                $dbInfo['status'] = 'complete';
                                $this->saveDatabaseList($path, $databases, $version, $type);
                            }

                        } catch (\Exception $e) {
                            $conn->rollback();
                            $output->write(sprintf('<error>Migration failed. Error %s</error>', $e->getMessage()));
                            throw $e;
                        }
                    } else {
                        $output->writeln(sprintf("<comment>queryList array is empty.</comment>"));
                    }
                } else {
                    $output->writeln(sprintf("<comment>Nothing to execute for section $section!</comment>"));

                    return false;
                }
            }
        }
        $this->queryList = array();

        return true;
    }

    /**
     *
     * Reads a sql file and adds queries  in the queryList array.
     *
     * @param string $sqlFilePath
     * @param OutputInterface $output
     * @param string type
     */
    public function fillQueryList($sqlFilePath, $output, $type)
    {
        $output->writeln(sprintf("Processing file type: $type '<info>%s</info>'... ", $sqlFilePath));
        $sections = $this->getSections();

        foreach ($sections as $section) {
            $sqlList = $this->getSQLContents($sqlFilePath, $section, $output);
            $this->setQueryList($sqlList, $section, $type);
        }
    }

    /**
     * Setting the queryList array
     *
     * @param array $queryList
     * @param string $section
     * @param string $type
     */
    public function setQueryList($queryList, $section, $type)
    {
        if (!isset($this->queryList[$type][$section])) {
            $this->queryList[$type][$section] = $queryList;
        } else {
            $this->queryList[$type][$section] = array_merge($this->queryList[$type][$section], $queryList);
        }
    }

    /**
     * Returns sections
     * @return array
     */
    public function getSections()
    {
        return array(
            'main',
            'user',
            'stats',
            'scorm',
            'course'
        );
    }

    /**
     * Generates database array info
     *
     * @param array $courseList
     * @return array
     */
    public function generateDatabaseList($courseList)
    {
        $courseDbList = array();
        $_configuration = $this->getConfigurationArray();
        if (!empty($courseList)) {
            foreach ($courseList as $course) {
                if (!empty($course['db_name'])) {
                    $courseDbList[] = array(
                        'database' => '_chamilo_course_'.$course['db_name'],
                        'prefix' => $this->getTablePrefix($_configuration, $course['db_name']),
                        'status' => 'waiting'
                    );
                }
            }
        } else {
            $courseDbList = array(
                array(
                    'database'=> 'main_database',
                    'status' => 'waiting',
                    'prefix' => null
                )
            );
        }

        $databaseSection = array(
            'main' => array(
                array(
                    'database' => 'main_database',
                    'status' => 'waiting'
                )
            ),
            'user' => array(
                array(
                    'database' => 'user_personal_database',
                    'status' => 'waiting'
                )
            ),
            'stats' => array(
                array(
                    'database' => 'statistics_database',
                    'status' => 'waiting'
                )
            ),
            'course'=> $courseDbList
        );

        $this->setDatabaseList($databaseSection);
        return $this->databaseList;
    }

    /**
     * Sets the database list
     * @param array $list
     */
    public function setDatabaseList($list)
    {
        $this->databaseList = $list;
    }

    /**
     * @param OutputInterface $output
     * @param array $courseList
     * @param string $path
     * @param string $version
     * @param string $type
     *
     * @return mixed|void
     */
    public function getDatabaseList($output, $courseList, $path, $version, $type)
    {
        return $this->generateDatabaseList($courseList);

        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);
        $newConfigurationFile = $configurationPath.'db_migration_status_'.$version.'_'.$type.'.yml';
        if (file_exists($newConfigurationFile)) {
            $yaml = new Parser();
            $output->writeln("<comment>Loading databases list status from file:</comment> <info>$newConfigurationFile</info>");

            return $yaml->parse(file_get_contents($newConfigurationFile));
        } else {

            return $this->generateDatabaseList($courseList);
        }
    }

    /**
     * @param string $path
     * @param string $databaseSection
     * @param string $version
     * @param string $type
     *
     * @return bool
     */
    public function saveDatabaseList($path, $databaseSection, $version, $type)
    {
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);
        $dumper = new Dumper();
        $yaml = $dumper->dump($databaseSection, 2); //inline
        $newConfigurationFile = $configurationPath.'db_migration_status_'.$version.'_'.$type.'.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);
    }

    /**
     * @param OutputInterface $output
     * @param array $courseList
     * @param string $path
     * @param string $section
     * @param string $version
     * @param string $type
     *
     * @return mixed
     */
    public function getDatabasesPerSection($output, $courseList, $path, $section, $version, $type)
    {
        $databases = $this->getDatabaseList($output, $courseList, $path, $version, $type);
        if (isset($databases[$section])) {
            return $databases[$section];
        }
    }

    /**
     * Function originally wrote in install.lib.php
     *
     * @param string $file
     * @param string $section
     * @param OutputInterface $output
     *
     * @return array|bool
     */
    public function getSQLContents($file, $section, $output)
    {
        if (empty($file) || file_exists($file) == false) {
            $output->writeln(sprintf("File doesn't exist: '<info>%s</info>'... ", $file));
            return false;
        }

        if (!in_array($section, array('main', 'user', 'stats', 'scorm', 'course'))) {
            $output->writeln(sprintf("Section is <info>%s</info> not authorized in getSQLContents()", $section));
            return false;
        }

        // Empty lines should not be executed as SQL statements, because errors occur, see Task #2167.
        $fileContents = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($fileContents) or count($fileContents) < 1) {
            $output->writeln(sprintf("File '<info>%s</info>' looks empty in getSQLContents()", $file));
            return false;
        }

        // Prepare the resulting array
        $sectionContents = array();
        $record = false;
        foreach ($fileContents as $line) {
            if (substr($line, 0, 2) == '--') {
                //This is a comment. Check if section name, otherwise ignore
                $result = array();
                if (preg_match('/^-- xx([A-Z]*)xx/', $line, $result)) { //we got a section name here
                    if ($result[1] == strtoupper($section)) {
                        //we have the section we are looking for, start recording
                        $record = true;
                    } else {
                        //we have another section's header. If we were recording, stop now and exit loop
                        if ($record) {
                            break;
                        }
                        $record = false;
                    }
                }
            } else {
                if ($record) {
                    if (!empty($line)) {
                        $sectionContents[] = $line;
                    }
                }
            }
        }

        return $sectionContents;
    }

    /**
     * Creates the course tables with the prefix c_
     * @param OutputInterface $output
     * @param string $dryRun
     * @return int
     */
    public function createCourseTables($output, $dryRun)
    {
        if ($dryRun) {
            $output->writeln("<comment>Creating c_* tables but dry-run is on. 0 table created.</comment>");
            return 0;
        }

        $output->writeln('<comment>Creating course tables (c_*)</comment>');

        $command = $this->getApplication()->find('dbal:import');
        $sqlFolder = $this->getInstallationPath('1.9.0');

        // Importing sql files.
        $arguments = array(
            'command' => 'dbal:import',
            'file' =>  $sqlFolder.'db_course.sql'
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }

    /**
     * Gets course tables
     * @return array
     */
    private function getCourseTables()
    {
        $tables = array(
            'tool',
            'tool_intro',
            'group_info',
            'group_category',
            'group_rel_user',
            'group_rel_tutor',
            'item_property',
            'userinfo_content',
            'userinfo_def',
            'course_description',
            'calendar_event',
            'calendar_event_repeat',
            'calendar_event_repeat_not',
            'calendar_event_attachment',
            'announcement',
            'announcement_attachment',
            'resource',
            'student_publication',
            'student_publication_assignment',
            'document',
            'forum_category',
            'forum_forum',
            'forum_thread',
            'forum_post',
            'forum_mailcue',
            'forum_attachment',
            'forum_notification',
            'forum_thread_qualify',
            'forum_thread_qualify_log',
            'link',
            'link_category',
            'online_connected',
            'online_link',
            'chat_connected',
            'quiz',
            'quiz_rel_question',
            'quiz_question',
            'quiz_answer',
            'quiz_question_option',
            'quiz_category',
            'quiz_question_rel_category',
            'dropbox_post',
            'dropbox_file',
            'dropbox_person',
            'dropbox_category',
            'dropbox_feedback',
            'lp',
            'lp_item',
            'lp_view',
            'lp_item_view',
            'lp_iv_interaction',
            'lp_iv_objective',
            'blog',
            'blog_comment',
            'blog_post',
            'blog_rating',
            'blog_rel_user',
            'blog_task',
            'blog_task_rel_user',
            'blog_attachment',
            'permission_group',
            'permission_user',
            'permission_task',
            'role',
            'role_group',
            'role_permissions',
            'role_user',
            'survey',
            'survey_question',
            'survey_question_option',
            'survey_invitation',
            'survey_answer',
            'survey_group',
            'wiki',
            'wiki_conf',
            'wiki_discuss',
            'wiki_mailcue',
            'course_setting',
            'glossary',
            'notebook',
            'attendance',
            'attendance_sheet',
            'attendance_calendar',
            'attendance_result',
            'attendance_sheet_log',
            'thematic',
            'thematic_plan',
            'thematic_advance',
            'metadata'
        );
        return $tables;
    }
}
