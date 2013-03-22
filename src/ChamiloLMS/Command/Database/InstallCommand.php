<?php

namespace ChamiloLMS\Command\Database;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console;
use Symfony\Component\Console\Input\ArrayInput;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Yaml\Dumper;

/**
 * Class MigrationCommand
 */
class InstallCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:install')
            ->setDescription('Execute a Chamilo installation to a specified version')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version to migrate to.', null)
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the chamilo folder');
    }

    /**
     * Gets the configuration folder
     *
     * @return string
     */
    public function getConfigurationFile()
    {
        return api_get_path(SYS_PATH).'main/inc/conf/';
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
     * Executes a command via CLI
     *
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $configurationPath = $this->getConfigurationFile();

        $dialog = $this->getHelperSet()->get('dialog');

        $version = $input->getArgument('version');
        $defaultVersion = $this->getLatestVersion();

        if (empty($version)) {
            $version = $defaultVersion;
        }

        $output->writeln("<comment>Welcome to the Chamilo installation process!</comment>");

        if (!is_writable($configurationPath)) {
            $output->writeln("<comment>Folder ".$configurationPath." must be writable</comment>");
        }

        $sqlFolder = $this->getInstallationPath($version);

        if (!is_dir($sqlFolder)) {
            $output->writeln("<comment>Sorry you can't install Chamilo :( Installation files for version $version does not exists: </comment><info>".$sqlFolder);

            return false;
        }

        /*if (!$dialog->askConfirmation(
            $output,
            '<comment>You are about to install Chamilo </comment><info>$version</info> <comment>here:</comment>'.$configurationPath.'</info> <question>Are you sure?</question>(y/N)',
            false
        )
        ) {
            return;
        }*/

        /*
        if (file_exists($configurationPath.'configuration.php') || file_exists($configurationPath.'configuration.yml')) {
            if (!$dialog->askConfirmation(
                $output,
                '<question>There is a Chamilo installation located here:</question> '.$configurationPath.' <question>Are you sure you want to continue?</question>(y/N)',
                false
            )
            ) {
                return;
            }
        }*/

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

        //Installing database
        $result = $this->install($version, $newConfigurationArray, $output);

        if ($result) {
            $this->createAdminUser($newConfigurationArray, $output);
            $this->writeConfiguration($newConfigurationArray, $version);
            $output->writeln("<comment>Database installation finished!</comment>");
        }
    }

    /**
     *
     * @param $newConfigurationArray
     * @param $output
     * @return bool
     */
    public function createAdminUser($newConfigurationArray, $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        //Creating admin user
        $output->writeln("<comment>Chamilo was successfully installed visit: </comment> <info>".$newConfigurationArray['root_web']);

        $adminUser = array(
            'lastname' => 'Julio',
            'firstname' => 'M',
            'username' => 'admin',
            'password' => 'admin',
            'email' => 'admin@example.org'
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
        $userInfo = \UserManager::add($userInfo);
        if ($userInfo && isset($userInfo['user_id'])) {
            $userId = $userInfo['user_id'];
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
        $configurationPath = $this->getConfigurationFile();

        $newConfigurationArray['system_version'] = $version;
        $dumper = new Dumper();
        $yaml = $dumper->dump($newConfigurationArray, 2); //inline
        $newConfigurationFile = $configurationPath.'configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);
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
        $output->writeln("<comment>Creating database ... </comment>");

        $result = $this->createDatabase($_configuration);

        //Importing files
        if ($result) {
            $command = $this->getApplication()->find('dbal:import');

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

            //Getting extra information about the installation
            $result = \Database::query("SELECT selected_value FROM ".\Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT)." WHERE variable = 'chamilo_database_version'");
            $result = \Database::fetch_array($result);

            $output->writeln("<comment>Showing chamilo_database_version value:</comment> ".$result['selected_value']);

            return true;
        }
    }

    /**
     * Creates a Database
     * @todo use doctrine?
     *
     * @return resource
     */
    public function createDatabase($_configuration)
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
        return  \Database::query("CREATE DATABASE IF NOT EXISTS ".mysql_real_escape_string($_configuration['main_database'])." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci");
    }
}