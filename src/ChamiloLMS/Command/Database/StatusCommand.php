<?php

namespace ChamiloLMS\Command\Database;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Class StatusCommand
 */
class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:status')
            ->setDescription('Show the information of the current Chamilo installation')
            ->addOption('configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.');
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
        global $_configuration;

        if (!isset($_configuration['root_sys'])) {
            $output->writeln("<comment>Chamilo is not installed here!</comment>");
            exit;
        }

        $configurationPath = api_get_path(SYS_PATH).'main/inc/conf/';

        $query = "SELECT selected_value FROM settings_current WHERE variable = 'chamilo_database_version'";
        $conn = $this->getHelper('main_database')->getConnection();
        $data = $conn->executeQuery($query);
        $data = $data->fetch();

        $chamiloVersion = $data['selected_value'];
        $output->writeln('<comment>Chamilo status</comment>');
        $output->writeln("<comment>Chamilo configuration path:</comment> <info>".$configurationPath."</info>");

        $output->writeln('<comment>Chamilo $_configuration[system_version]:</comment> <info>'.$_configuration['system_version'].'</info>');
        $output->writeln("<comment>Chamilo setting: 'chamilo_database_version':</comment> <info>".$chamiloVersion."</info>");
    }

}

