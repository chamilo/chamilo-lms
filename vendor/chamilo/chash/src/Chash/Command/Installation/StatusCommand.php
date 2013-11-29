<?php

namespace Chash\Command\Installation;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Class StatusCommand
 * @package Chash\Command\Installation
 */
class StatusCommand extends CommonCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:status')
            ->setDescription('Show the information of the current Chamilo installation')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the Chamilo folder');
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

        $_configuration = $this->getConfigurationHelper()->getConfiguration($path);

        if (empty($_configuration)) {
            $output->writeln("<comment>Chamilo is not installed here! You may add a path as an option:</comment>");
            $output->writeln("<comment>For example: </comment><info>chamilo:status --path=/var/www/chamilo</info>");
            return 0;
        }

        $databaseSettings = array(
            'driver' => 'pdo_mysql',
            'host' => $_configuration['db_host'],
            'dbname' => $_configuration['main_database'],
            'user' => $_configuration['db_user'],
            'password' => $_configuration['db_password']
        );

        // single_database

        $this->setDatabaseSettings($databaseSettings);
        $this->setDoctrineSettings();

        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->getHelper('db')->getConnection();

        $query = "SELECT selected_value FROM settings_current WHERE variable = 'chamilo_database_version'";
        $data = $conn->executeQuery($query);
        $data = $data->fetch();
        $chamiloVersion = $data['selected_value'];
        $databaseSetting = 'chamilo_database_version';

        if (empty($chamiloVersion)) {
            $query = "SELECT selected_value FROM settings_current WHERE variable = 'dokeos_database_version'";
            $data = $conn->executeQuery($query);
            $data = $data->fetch();
            $chamiloVersion = $data['selected_value'];
            $databaseSetting = 'dokeos_database_version';
        }

        $output->writeln('<comment>Chamilo $_configuration info:</comment>');
        $output->writeln('');

        $output->writeln('<comment>Chamilo $_configuration[root_web]:</comment> <info>'.$_configuration['root_web'].'</info>');
        if (isset($_configuration['root_sys'])) {
            $output->writeln('<comment>Chamilo $_configuration[root_sys]:</comment> <info>'.$_configuration['root_sys'].'</info>');
        }

        //$output->writeln('<comment>Chamilo $_configuration[db_driver]:</comment> <info>'.$_configuration['db_driver'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[main_database]:</comment> <info>'.$_configuration['main_database'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[db_host]:</comment> <info>'.$_configuration['db_host'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[db_user]:</comment> <info>'.$_configuration['db_user'].'</info>');
        $output->writeln('<comment>Chamilo $_configuration[db_password]:</comment> <info>'.$_configuration['db_password'].'</info>');

        if (isset($_configuration['db_port'])) {
            $output->writeln('<comment>Chamilo $_configuration[db_port]:</comment> <info>'.$_configuration['db_port'].'</info>');
        }

        if (isset($_configuration['single_database'])) {
            $output->writeln('<comment>Chamilo $_configuration[single_database]:</comment> <info>'.$_configuration['single_database'].'</info>');
        }

        if (isset($_configuration['db_prefix'])) {
            $output->writeln('<comment>Chamilo $_configuration[db_prefix]:</comment> <info>'.$_configuration['db_prefix'].'</info>');
        }

        if (isset($_configuration['db_glue'])) {
            $output->writeln('<comment>Chamilo $_configuration[db_glue]:</comment> <info>'.$_configuration['db_glue'].'</info>');
        }

        if (isset($_configuration['db_prefix'])) {
            $output->writeln('<comment>Chamilo $_configuration[table_prefix]:</comment> <info>'.$_configuration['table_prefix'].'</info>');
        }
        $output->writeln('');

        if (empty($chamiloVersion)) {
            $output->writeln("<comment>Please check your Chamilo installation carefully the <info>'chamilo_database_version'</info> admin does not exists.</comment>");
        } else {
            $output->writeln('<comment>Chamilo database settings:</comment>');
            $output->writeln("<comment>Chamilo setting_current['".$databaseSetting."']:</comment> <info>".$chamiloVersion."</info>");
        }

        if (isset($_configuration['system_version'])) {
            $output->writeln('<comment>Chamilo $_configuration[system_version]:</comment> <info>'.$_configuration['system_version'].'</info>');
        }

        if (!version_compare(substr($chamiloVersion, 0, 5), substr($_configuration['system_version'], 0, 5), '==' )) {
            /*$output->writeln("<error>Please check carefully your Chamilo installation. </error>");
            $output->writeln("<comment>The configuration.php file and the 'chamilo_database_version' setting are not synced.</comment>");*/
        }

    }

}
