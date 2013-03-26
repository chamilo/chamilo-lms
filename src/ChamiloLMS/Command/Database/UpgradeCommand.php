<?php

namespace ChamiloLMS\Command\Database;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

//Constant needed in order to call migrate-db-* and update-files-*
define('SYSTEM_INSTALLATION', 1);

/**
 * Class UpgradeCommand
 */
class UpgradeCommand extends AbstractCommand
{
    public $queryList;
    public $databaseList;

    protected function configure()
    {
        $this
            ->setName('chamilo:upgrade')
            ->setDescription('Execute a chamilo migration to a specified version or the latest available version.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
            ->addOption('configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the chamilo folder');
    }

    /**
     * Gets the min version available to migrate
     * @return mixed
     */
    public function getMinVersionSupportedByInstall()
    {
        return key($this->availableVersions());
    }

    /**
     * Gets an array with the supported Chamilo versions to migrate
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
                'post' => 'null',
                'update_db' => 'update-db-1.8.7-1.8.8.inc.php',
                'update_files' => 'update-files-1.8.7-1.8.8.inc.php',
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
                'post' => 'null',
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
            '1.10'  => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.9.0-1.10.0-pre.sql',
                'post' => 'migrate-db-1.9.0-1.10.0-post.sql',
                'update_db' => 'update-db-1.9.0-1.10.0.inc.php',
                //'update_files' => '',
                'hook_to_doctrine_version' => '10'
            )
        );

        return $versionList;
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
        $version = $input->getArgument('version');
        $dryRun = $input->getOption('dry-run');

        $_configuration = $this->getHelper('configuration')->getConfiguration();

        if (!isset($_configuration['root_sys'])) {
            $output->writeln("<comment>Chamilo is not installed here!</comment>");
            exit;
        }

        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);

        //Checking configuration file
        if (!is_writable($configurationPath)) {
            $output->writeln("<comment>Folder ".$configurationPath." must have writable permissions</comment>");
        }

        $minVersion = $this->getMinVersionSupportedByInstall();
        $versionList = $this->availableVersions();

        $input->setOption('configuration', $this->getMigrationConfigurationFile());

        $configuration = $this->getMigrationConfiguration($input, $output);

        //Doctrine migrations version
        $doctrineVersion = $configuration->getCurrentVersion();

        $versionNameList = $this->getVersionNumberList();

        //Checking version
        if (!in_array($version, $versionNameList)) {
            $output->writeln("<comment>Version '$version' is not available</comment>");
            $output->writeln("<comment>Available versions: </comment><info>".implode(', ', $versionNameList)."</info>");
            exit;
        }

        $currentVersion = null;

        //Checking root_sys and correct Chamilo version to install
        if (!isset($_configuration['root_sys'])) {
            $output->writeln("<comment>Can't migrate Chamilo. This is not a Chamilo folder installation.</comment>");
        }

        //Checking system_version

        if (!isset($_configuration['system_version']) || empty($_configuration['system_version'])) {
            $output->writeln("<comment>You have something wrong in your Chamilo installation check it with chamilo:status.</comment>");
            exit;
        }

        if (version_compare($_configuration['system_version'], $minVersion, '<')) {
            $output->writeln("<comment>Your Chamilo version is not supported! The minimun version is: </comment><info>$minVersion</info> <comment>You want to update from <info>".$_configuration['system_version']."</info> <comment>to</comment> <info>$minVersion</info>");
            exit;
        }

        if (version_compare($version, $_configuration['system_version'], '>')) {
            $currentVersion = $_configuration['system_version'];
        } else {
            $output->writeln("<comment>Please provide a version greater than </comment><info>".$_configuration['system_version']."</info> <comment>your selected version: </comment><info>$version</info>");
            $output->writeln("<comment>You can also check your installation health's with </comment><info>chamilo:status");
            exit;
        }

        $versionInfo = $this->getAvailableVersionInfo($version);

        if (isset($versionInfo['hook_to_doctrine_version']) && isset($doctrineVersion)) {
            if ($doctrineVersion == $versionInfo['hook_to_doctrine_version']) {
                $output->writeln("<comment>You already have the latest version. Nothing to update! Doctrine version $doctrineVersion</comment>");
                exit;
            }
        }

        if (isset($versionInfo['parent']) && !empty($versionInfo['parent'])) {
            $versionInfoParent = $this->getAvailableVersionInfo($versionInfo['parent']);
            if ($doctrineVersion == $versionInfoParent['hook_to_doctrine_version']) {
                $output->writeln("<comment>You already have the latest version. Nothing to update! Doctrine version $doctrineVersion</comment>");
                exit;
            }
        }

        $output->writeln("<comment>Welcome to the Chamilo upgrade!</comment>");

        //Too much questions?

        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to update Chamilo located here?</question> '.$_configuration['root_sys'].' (y/N)',
            false
        )
        ) {
            return;
        }

        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to update from version</question> <info>'.$_configuration['system_version'].'</info> <comment>to version </comment><info>'.$version.'</info> (y/N)',
            false
        )
        ) {
            return;
        }

        $output->writeln('<comment>Migrating from Chamilo version: </comment><info>'.$_configuration['system_version'].'</info> <comment>to version <info>'.$version);

        //Starting
        $output->writeln('<comment>Starting upgrade for Chamilo with configuration file: </comment><info>'.$configurationPath.'configuration.php</info>');

        $oldVersion = $currentVersion;
        foreach ($versionList as $versionItem => $versionInfo) {
            if (version_compare($versionItem, $currentVersion, '>') && version_compare($versionItem, $version, '<=')) {
                $output->writeln("----------------------------------------------------------------");
                $output->writeln("<comment>Starting migration from version: </comment><info>$oldVersion</info><comment> to </comment><info>$versionItem ");
                $output->writeln("");

                if (isset($versionInfo['require_update']) && $versionInfo['require_update'] == true) {
                    //Greater than my current version
                    $this->startMigration($oldVersion, $versionItem, $dryRun, $output);
                    $oldVersion = $versionItem;
                    $output->writeln("----------------------------------------------------------------");
                } else {
                    $output->writeln("<comment>Version <info>'$versionItem'</info> does not need a DB migration</comment>");
                }
            }
        }

        $this->updateConfiguration($version);

        $output->writeln("<comment>wow! You just finish to migrate. Too check the current status of your platform. Run </comment><info>chamilo:status</info>");
    }

    /**
     * Starts a migration
     *
     * @param $fromVersion
     * @param $toVersion
     * @param $dryRun
     * @param $output
     *
     * @return bool
     */
    public function startMigration($fromVersion, $toVersion, $dryRun, $output)
    {
        //Needed when using require file
        $_configuration = $this->getHelper('configuration')->getConfiguration();

        $installPath = api_get_path(SYS_CODE_PATH).'install/'.$toVersion.'/';
        $versionInfo = $this->getAvailableVersionInfo($toVersion);

        //Filling sqlList with "pre" db changes
        if (isset($versionInfo['pre']) && !empty($versionInfo['pre'])) {
            $sqlToInstall = $installPath.$versionInfo['pre'];
            $this->processSQLFile($sqlToInstall, $output);
        }

        //Filling sqlList with "post" db changes
        if (isset($versionInfo['post']) && !empty($versionInfo['post'])) {
            $sqlToInstall = $installPath.$versionInfo['post'];
            $this->processSQLFile($sqlToInstall, $output);
        }

        $result = $this->processQueryList($output, $toVersion);
        exit;

        //Processing "db" changes
        if (isset($versionInfo['update_db']) && !empty($versionInfo['update_db'])) {
            $sqlToInstall = $installPath.$versionInfo['update_db'];
            if (is_file($sqlToInstall) && file_exists($sqlToInstall)) {
                $output->writeln("<comment>Executing update db file: <info>'$sqlToInstall'</info>");
                $dblist = \Database::get_databases();
                require $sqlToInstall;
            }
        }

        //Processing "update file" changes
        if (isset($versionInfo['update_files']) && !empty($versionInfo['update_files'])) {
            $sqlToInstall = $installPath.$versionInfo['update_files'];
            if (is_file($sqlToInstall) && file_exists($sqlToInstall)) {
                $output->writeln("<comment>Executing update files file: <info>'$sqlToInstall'</info>");
                $dblist = \Database::get_databases();
                require $sqlToInstall;
            }
        }



        //$result = $this->processSQL($sqlToInstall, $dryRun, $output);
        //$result = true;
        $output->writeln('');
        $output->writeln("<comment>Executing pre file: <info>'$sqlToInstall'</info>");
        $output->writeln('');
        $output->writeln("<comment>You have to select yes for the 'Chamilo Migrations'<comment>");

/*
        if ($result) {
            $command = $this->getApplication()->find('migrations:migrate');
            $arguments = array(
                'command' => 'migrations:migrate',
                'version' => $versionInfo['hook_to_doctrine_version'],
                '--configuration' => $this->getMigrationConfigurationFile()
            );

            $output->writeln("<comment>Executing migrations:migrate ".$versionInfo['hook_to_doctrine_version']." --configuration=".$this->getMigrationConfigurationFile()."<comment>");

            $input = new ArrayInput($arguments);

            $command->run($input, $output);
            $output->writeln("<comment>Migration ended succesfully</comment>");
        }*/

        return false;
    }

    /**
     *
     */
    public function processQueryList($output, $version)
    {
        $dryRun = true;
        $databases = $this->getDatabaseList($version);

        foreach ($databases as $section => &$dbInfo) {

            if (isset($this->queryList[$section]) && !empty($this->queryList[$section])) {
                $queryList = $this->queryList[$section];
                try {
                    $lines = 0;
                    //$conn = $this->getHelper('main_database')->getConnection();
                    $conn = $this->getHelper($dbInfo['database'])->getConnection();
                    $conn->beginTransaction();

                    foreach ($queryList as $query) {
                        if ($dryRun) {
                            //$output->writeln($query);
                            $output->writeln('     <comment>-></comment> ' . $query);
                        } else {
                            $output->writeln('     <comment>-></comment> ' . $query);
                            $conn->executeQuery($query);
                        }
                        $lines++;
                    }

                    $conn->commit();

                    $database['status'] = 'complete';

                    //$this->saveDatabaseList($databases, $version);

                    if (!$dryRun) {
                       $output->writeln(sprintf('%d statements executed!', $lines) . PHP_EOL);

                       return true;
                    }
                } catch (\Exception $e) {
                    $conn->rollback();
                    $output->write(sprintf('<error>Migration failed. Error %s</error>', $e->getMessage()));
                    throw $e;
                }
            } else {
                $output->writeln(sprintf("Nothing to execute for section $section!"));
                return false;
            }
            return true;
        }
    }

    /**
     * @param string $sqlFilePath
     * @param $output
     */
    public function processSQLFile($sqlFilePath, $output)
    {
        if (is_file($sqlFilePath) && file_exists($sqlFilePath)) {
            $output->writeln(sprintf("Processing file '<info>%s</info>'... ", $sqlFilePath));
            $sections = $this->getSections();

            foreach ($sections as $section) {
                $sqlList = $this->getSQLContents($sqlFilePath, $section);
                $this->setQueryList($sqlList, $section);
            }
        } else {
            $output->writeln(sprintf("File does not exists: '<info>%s</info>'... ", $sqlFilePath));
        }
    }


    /**
     * Updates the configuration.yml file
     * @param string $version
     *
     * @return bool
     */
    public function updateConfiguration($version)
    {
        $_configuration = $this->getHelper('configuration')->getConfiguration();

        $configurationPath = $this->getHelper('configuration')->getConfigurationPath();

        $dumper = new Dumper();
        $_configuration['system_version'] = $version;

        $yaml = $dumper->dump($_configuration, 2); //inline
        $newConfigurationFile = $configurationPath.'configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);
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
     * @param $queryList
     * @param $section
     */
    public function setQueryList($queryList, $section)
    {
        if (!isset($this->queryList[$section])) {
            $this->queryList[$section] = $queryList;
        } else {
            $this->queryList[$section] = array_merge($this->queryList[$section], $queryList);
        }
    }

    /**
     *
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

    public function generateDatabaseList()
    {
        $courseList = \CourseManager::get_real_course_list();
        $courseDbList = array();

        foreach ($courseList as $course) {
            if (!empty($course['db_name'])) {
                $courseDbList[] = array(
                    'database' => '_chamilo_course_.'.$course['db_name'],
                    'status' => 'waiting'
                );
            }
        }

        if (empty($courseDbList)) {
            $courseDbList = array('main_database');
        }

        $databaseSection = array(
            'main'  => array(
                'database' => 'main_database',
                'status' => 'waiting'
            ),
            'user'  => array(
                'database' => 'user_personal_database',
                'status' => 'waiting'
            ),
            'stats' => array(
                'database' => 'statistics_database',
                'status' => 'waiting'
            ),
            //  'scorm' => array('main_database'),
            'course'=> $courseDbList
        );

        $this->setDatabaseList($databaseSection);

        return $this->databaseList;
    }

    public function setDatabaseList($list)
    {
        $this->databaseList = $list;
    }

    /**
     * @param string $version
     *
     * @return mixed|void
     */
    public function getDatabaseList($version)
    {
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath();
        $newConfigurationFile = $configurationPath.'db_migration_status_'.$version.'.yml';
        if (file_exists($newConfigurationFile)) {
            $yaml = new Parser();

            return $yaml->parse(file_get_contents($newConfigurationFile));
        } else {

            return $this->generateDatabaseList();
        }
    }

    /**
     * @param string $databaseSection
     * @param string $version
     *
     * @return bool
     */
    public function saveDatabaseList($databaseSection, $version)
    {
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath();
        $dumper = new Dumper();
        $yaml = $dumper->dump($databaseSection, 2); //inline
        $newConfigurationFile = $configurationPath.'db_migration_status_'.$version.'.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);
    }

    /**
     *
     * @param string $section
     *
     * @return mixed
     */
    public function getDatabasesPerSection($section, $version)
    {
        $databases = $this->getDatabaseList($version);
        if (isset($databases[$section])) {
            return $databases[$section];
        }
    }

    /**
     *
     * Converts a SQL file into a array of SQL queries in order to be executed by the Doctrine connection obj
     *
     * @param string $sqlFilePath
     * @param bool $dryRun
     * @param $output
     *
     * @return bool
     * @throws \Exception
     */
    private function processSQL($sqlFilePath, $dryRun, $output)
    {
        try {
            $lines = 0;
            $conn = $this->getHelper('main_database')->getConnection();

            $conn->beginTransaction();

            foreach ($sqlList as $query) {
                if ($dryRun) {
                    $output->writeln($query);
                } else {
                    $output->writeln('     <comment>-></comment> ' . $query);
                    $conn->executeQuery($query);
                }
                $lines++;
            }
            $conn->commit();

            if (!$dryRun) {
                $output->writeln(sprintf('%d statements executed!', $lines) . PHP_EOL);

                return true;
            }
        } catch (\Exception $e) {
            $conn->rollback();
            $output->write(sprintf('<error>Migration failed. Error %s</error>', $e->getMessage()));
            throw $e;
        }

        return false;
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

        //now we have our section's SQL statements group ready, return
        return $section_contents;
    }
}

