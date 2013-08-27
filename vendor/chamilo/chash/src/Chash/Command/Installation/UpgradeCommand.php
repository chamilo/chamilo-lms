<?php

namespace Chash\Command\Installation;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

// Constant needed in order to call migrate-db-* and update-files-*
define('SYSTEM_INSTALLATION', 1);

/**
 * Class UpgradeCommand
 */
class UpgradeCommand extends CommonCommand
{
    public $queryList;
    public $databaseList;

    /**
     * Get connection
     * @return\Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->getHelper('db')->getConnection();
    }

    protected function configure()
    {
        $this
            ->setName('chamilo:upgrade')
            ->setDescription('Execute a chamilo migration to a specified version or the latest available version.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the chamilo folder')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
            ->addOption('update-installation', null, InputOption::VALUE_OPTIONAL, 'Updates the portal with the current zip file. http:// or /var/www/file.zip')
            ->addOption('temp-folder', null, InputOption::VALUE_OPTIONAL, 'The temp folder.', '/tmp')
            ->addOption('silent', null, InputOption::VALUE_NONE, 'Execute the migration with out asking questions.');
            //->addOption('force', null, InputOption::VALUE_NONE, 'Force the update. Only for tests');
    }

    /**
     * Executes a command via CLI
     *
     * @param   Console\Input\InputInterface $input
     * @param   Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        // Setting up Chash:
        $command = $this->getApplication()->find('chash:setup');
        $arguments = array(
            'command' => 'chash:setup'
        );
        $inputSetup = new ArrayInput($arguments);
        $command->run($inputSetup, $output);

        // Arguments and options
        $version = $input->getArgument('version');
        $path = $input->getOption('path');
        $dryRun = $input->getOption('dry-run');
        $silent = $input->getOption('silent') == true;
        $tempFolder = $input->getOption('temp-folder');
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
            $output->writeln("<comment>Folder ".$configurationPath." must have writable permissions</comment>");
            return 0;
        }

        $this->setConfigurationPath($configurationPath);

        // In order to use Doctrine migrations

        // Setting configuration variable in order to get the doctrine version:
        //$input->setOption('configuration', $this->getMigrationConfigurationFile());
        //$configuration = $this->getMigrationConfiguration($input, $output);

        // Doctrine migrations version
        //$doctrineVersion = $configuration->getCurrentVersion();

        $doctrineVersion = null;

        // Getting supported version number list
        $versionNameList = $this->getVersionNumberList();
        $minVersion = $this->getMinVersionSupportedByInstall();
        $versionList = $this->availableVersions();

        // Checking version.
        if (!in_array($version, $versionNameList)) {
            $output->writeln("<comment>Version '$version' is not available.</comment>");
            $output->writeln("<comment>Available versions: </comment><info>".implode(', ', $versionNameList)."</info>");
            return 0;
        }

        $currentVersion = null;

        // Checking system_version.

        if (!isset($_configuration['system_version']) || empty($_configuration['system_version'])) {
            $output->writeln("<comment>You have something wrong in your Chamilo installation. Check it with the chamilo:status command</comment>");
            return 0;
        }

        if (version_compare($_configuration['system_version'], $minVersion, '<')) {
            $output->writeln("<comment>Your Chamilo version is not supported! The minimun version is: </comment><info>$minVersion</info> <comment>You want to upgrade from <info>".$_configuration['system_version']."</info> <comment>to</comment> <info>$minVersion</info>");
            return 0;
        }

        if (version_compare($version, $_configuration['system_version'], '>')) {
            $currentVersion = $_configuration['system_version'];
        } else {
            $output->writeln("<comment>Please provide a version greater than </comment><info>".$_configuration['system_version']."</info> <comment>you selected version: </comment><info>$version</info>");
            $output->writeln("<comment>You can also check your installation health with </comment><info>chamilo:status");
            return 0;
        }

        $versionInfo = $this->getAvailableVersionInfo($version);

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

        //$updateInstallation = '/home/jmontoya/Downloads/chamilo-lms-CHAMILO_1_9_6_STABLE.zip';
        //$updateInstallation = 'https://github.com/chamilo/chamilo-lms/archive/CHAMILO_1_9_6_STABLE.zip';

        if ($dryRun == false) {
            $chamiloLocationPath = $this->getPackage($output, $version, $updateInstallation, $tempFolder);
            if (empty($chamiloLocationPath)) {
                return;
            }
        }

        $this->writeCommandHeader($output, 'Welcome to the Chamilo upgrade process!');

        if ($dryRun == false) {
            $output->writeln("<comment>When the installation process finished the files located here:<comment>");
            $output->writeln("<info>$chamiloLocationPath</info>");
            $output->writeln("<comment>will be copied in your portal here: </comment><info>".$this->getRootSys()."</info>");
        } else {
            $output->writeln("<comment>When the installation process finished PHP files are not going to be updated (--dry-run is on).</comment>");
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
                '<question>Are you sure you want to upgrade from version</question> <info>'.$_configuration['system_version'].'</info> <comment>to version </comment><info>'.$version.'</info> (y/N)',
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

        if ($conn) {
            $output->writeln("<comment>Connection to the database established.</comment>");
        } else {
            $output->writeln("<comment>Can't connect to the DB with user:</comment><info>".$_configuration['db_user'])."</info>";
            return 0;
        }

        $query = "SELECT * FROM course";
        $result = $conn->executeQuery($query);
        $courseList = $result->fetchAll();

        $oldVersion = $currentVersion;
        foreach ($versionList as $versionItem => $versionInfo) {
            if (version_compare($versionItem, $currentVersion, '>') && version_compare($versionItem, $version, '<=')) {
                $output->writeln("----------------------------------------------------------------");
                $output->writeln("<comment>Starting migration from version: </comment><info>$oldVersion</info><comment> to </comment><info>$versionItem ");
                $output->writeln("");

                if (isset($versionInfo['require_update']) && $versionInfo['require_update'] == true) {
                    // Greater than my current version.
                    $this->startMigration($courseList, $path, $versionItem, $dryRun, $output);
                    $oldVersion = $versionItem;
                    $output->writeln("----------------------------------------------------------------");
                } else {
                    $output->writeln("<comment>Version <info>'$versionItem'</info> does not need a DB migration</comment>");
                }
            }
        }

        if ($dryRun == false) {
            $this->copyPackageIntoSystem($output, $chamiloLocationPath, null);
        }

        $this->updateConfiguration($output, $dryRun, array('system_version' => $version));

        $output->writeln("<comment>Hurrah!!! You just finish to migrate. Too check the current status of your platform. Run </comment><info>chamilo:status</info>");
    }

    /**
     * Starts a migration
     *
     * @param array $courseList
     * @param string $path
     * @param string $toVersion
     * @param bool $dryRun
     * @param Console\Output\OutputInterface $output
     *
     * @return bool
     */
    public function startMigration($courseList, $path, $toVersion, $dryRun, Console\Output\OutputInterface $output)
    {
        // Cleaning query list.
        $this->queryList = array();

        // Main DB connection.
        $conn = $this->getConnection();

        $_configuration = $this->getHelper('configuration')->getConfiguration($path);

        $versionInfo = $this->getAvailableVersionInfo($toVersion);
        $installPath = $this->getInstallationFolder().$toVersion.'/';

        // Filling sqlList array with "pre" db changes.
        if (isset($versionInfo['pre']) && !empty($versionInfo['pre'])) {
            $sqlToInstall = $installPath.$versionInfo['pre'];
            $this->fillQueryList($sqlToInstall, $output, 'pre');

            // Processing sql query list depending of the section (course, main, user).
            $this->processQueryList($courseList, $output, $path, $toVersion, $dryRun, 'pre');
        }

        // Filling sqlList array with "post" db changes.
        if (isset($versionInfo['post']) && !empty($versionInfo['post'])) {
            $sqlToInstall = $installPath.$versionInfo['post'];
            $this->fillQueryList($sqlToInstall, $output, 'post');
            // Processing sql query list depending of the section.
            $this->processQueryList($courseList, $output, $path, $toVersion, $dryRun, 'post');
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
                $update($_configuration, $conn, $courseList, $dryRun, $output);
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
                    $updateFiles($_configuration, $conn, $courseList, $dryRun, $output);
                }
            }
        }

        if (1) {

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
            $command->run($input, $output);

            $output->writeln("<comment>Migration ended successfully</comment>");

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
                '--dry-run' => $dryRun
            );

            $input = new ArrayInput($arguments);
            $command->run($input, $output);

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
     * @param $output
     * @param $version
     * @param $dryRun
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
                $output->writeln("<comment>Loading section:</comment> <info>$section</info> <comment>using database key </comment><info>".$dbInfo['database']."</info>");
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

                    try {
                        $lines = 0;

                        /** @var \Doctrine\DBAL\Connection $conn */
                        $conn = $this->getHelper($dbInfo['database'])->getConnection();
                        $output->writeln("<comment>Executing queries in DB:</comment> <info>".$conn->getDatabase()."</info>");

                        $conn->beginTransaction();

                        foreach ($queryList as $query) {
                            // Add a prefix.

                            if ($section == 'course') {
                                //var_dump($dbInfo);
                                $query = str_replace('{prefix}', $dbInfo['prefix'], $query);
                                //var_dump($query);
                                //var_dump($conn->getParams());
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
                            $conn->commit();
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
     * @param $output
     * @param string type
     */
    public function fillQueryList($sqlFilePath, $output, $type)
    {
        if (is_file($sqlFilePath) && file_exists($sqlFilePath)) {
            $output->writeln(sprintf("Processing file type: $type '<info>%s</info>'... ", $sqlFilePath));
            $sections = $this->getSections();

            foreach ($sections as $section) {
                $sqlList = $this->getSQLContents($sqlFilePath, $section);
                $this->setQueryList($sqlList, $section, $type);
            }
        } else {
            $output->writeln(sprintf("File does not exists: '<info>%s</info>'... ", $sqlFilePath));
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
            'main'  => array(
                array(
                    'database' => 'main_database',
                    'status' => 'waiting'
                )
            ),
            'user'  => array(
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
     * @param $list
     */
    public function setDatabaseList($list)
    {
        $this->databaseList = $list;
    }

    /**
     *
     * @param string $path
     * @param string $version
     * @param string $type
     *
     * @return mixed|void
     */
    public function getDatabaseList($output, $courseList, $path, $version, $type)
    {
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);
        $newConfigurationFile = $configurationPath.'db_migration_status_'.$version.'_'.$type.'.yml';

        return $this->generateDatabaseList($courseList);

        if (file_exists($newConfigurationFile)) {
            $yaml = new Parser();
            $output->writeln("<comment>Loading database list status from file:</comment> <info>$newConfigurationFile</info>");

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
     *
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
     * @param bool $printErrors
     *
     * @return array|bool
     */
    public function getSQLContents($file, $section, $printErrors = true)
    {
        //check given parameters
        if (empty($file)) {
            $error = "Missing name of file to parse in get_sql_file_contents()";
            if ($printErrors) {
                echo $error;
            }

            return false;
        }
        if (!in_array($section, array('main', 'user', 'stats', 'scorm', 'course'))) {
            $error = "Section '$section' is not authorized in getSQLContents()";
            if ($printErrors) {
                echo $error;
            }

            return false;
        }
        $filepath = $file;
        if (!is_file($filepath) or !is_readable($filepath)) {
            $error = "File $filepath not found or not readable in getSQLContents()";
            if ($printErrors) {
                echo $error;
            }

            return false;
        }
        //read the file in an array
        // Empty lines should not be executed as SQL statements, because errors occur, see Task #2167.
        $file_contents = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($file_contents) or count($file_contents) < 1) {
            $error = "File $filepath looks empty in getSQLContents()";
            if ($printErrors) {
                echo $error;
            }

            return false;
        }

        //prepare the resulting array
        $section_contents = array();
        $record = false;
        foreach ($file_contents as $index => $line) {
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
                        $section_contents[] = $line;
                    }
                }
            }
        }

        // Now we have our section's SQL statements group ready, return

        return $section_contents;
    }


    /**
     * Executed only before createCourseTables()
     */
    public function dropCourseTables()
    {
        $list = $this->getCourseTables();
        foreach ($list as $table) {
            $sql = "DROP TABLE IF EXISTS ".DB_COURSE_PREFIX.$table;
            //\Database::query($sql);
        }
    }

    /**
     * Creates the course tables with the prefix c_
     * @param $output
     * @param string $dryRun
     */
    public function createCourseTables($output, $dryRun)
    {

        if ($dryRun) {
            $output->writeln("<comment>Creating c_* tables but dry-run is on. 0 tables created.</comment>");
            return 0;
        }

        $output->writeln('<comment>Creating course tables (c_*)</comment>');

        $command = $this->getApplication()->find('dbal:import');
        $sqlFolder = $this->getInstallationPath('1.9.0');

        //Importing sql files
        $arguments = array(
            'command' => 'dbal:import',
            'file' =>  $sqlFolder.'db_course.sql'
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }

    /**
     * @return array
     */
    private function getCourseTables()
    {
        $tables = array();

        $tables[]= 'tool';
        $tables[]= 'tool_intro';
        $tables[]= 'group_info';
        $tables[]= 'group_category';
        $tables[]= 'group_rel_user';
        $tables[]= 'group_rel_tutor';
        $tables[]= 'item_property';
        $tables[]= 'userinfo_content';
        $tables[]= 'userinfo_def';
        $tables[]= 'course_description';
        $tables[]= 'calendar_event';
        $tables[]= 'calendar_event_repeat';
        $tables[]= 'calendar_event_repeat_not';
        $tables[]= 'calendar_event_attachment';
        $tables[]= 'announcement';
        $tables[]= 'announcement_attachment';
        $tables[]= 'resource';
        $tables[]= 'student_publication';
        $tables[]= 'student_publication_assignment';
        $tables[]= 'document';
        $tables[]= 'forum_category';
        $tables[]= 'forum_forum';
        $tables[]= 'forum_thread';
        $tables[]= 'forum_post';
        $tables[]= 'forum_mailcue';
        $tables[]= 'forum_attachment';
        $tables[]= 'forum_notification';
        $tables[]= 'forum_thread_qualify';
        $tables[]= 'forum_thread_qualify_log';
        $tables[]= 'link';
        $tables[]= 'link_category';
        $tables[]= 'online_connected';
        $tables[]= 'online_link';
        $tables[]= 'chat_connected';
        $tables[]= 'quiz';
        $tables[]= 'quiz_rel_question';
        $tables[]= 'quiz_question';
        $tables[]= 'quiz_answer';
        $tables[]= 'quiz_question_option';
        $tables[]= 'quiz_category';
        $tables[]= 'quiz_question_rel_category';
        $tables[]= 'dropbox_post';
        $tables[]= 'dropbox_file';
        $tables[]= 'dropbox_person';
        $tables[]= 'dropbox_category';
        $tables[]= 'dropbox_feedback';
        $tables[]= 'lp';
        $tables[]= 'lp_item';
        $tables[]= 'lp_view';
        $tables[]= 'lp_item_view';
        $tables[]= 'lp_iv_interaction';
        $tables[]= 'lp_iv_objective';
        $tables[]= 'blog';
        $tables[]= 'blog_comment';
        $tables[]= 'blog_post';
        $tables[]= 'blog_rating';
        $tables[]= 'blog_rel_user';
        $tables[]= 'blog_task';
        $tables[]= 'blog_task_rel_user';
        $tables[]= 'blog_attachment';
        $tables[]= 'permission_group';
        $tables[]= 'permission_user';
        $tables[]= 'permission_task';
        $tables[]= 'role';
        $tables[]= 'role_group';
        $tables[]= 'role_permissions';
        $tables[]= 'role_user';
        $tables[]= 'survey';
        $tables[]= 'survey_question';
        $tables[]= 'survey_question_option';
        $tables[]= 'survey_invitation';
        $tables[]= 'survey_answer';
        $tables[]= 'survey_group';
        $tables[]= 'wiki';
        $tables[]= 'wiki_conf';
        $tables[]= 'wiki_discuss';
        $tables[]= 'wiki_mailcue';
        $tables[]= 'course_setting';
        $tables[]= 'glossary';
        $tables[]= 'notebook';
        $tables[]= 'attendance';
        $tables[]= 'attendance_sheet';
        $tables[]= 'attendance_calendar';
        $tables[]= 'attendance_result';
        $tables[]= 'attendance_sheet_log';
        $tables[]= 'thematic';
        $tables[]= 'thematic_plan';
        $tables[]= 'thematic_advance';
        $tables[]= 'metadata';
        return $tables;
    }
}

