<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Chash\Command\Installation\CommonCommand;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Class CommonDatabaseCommand
 * @package Chash\Command\Database
 */
class CommonDatabaseCommand extends CommonCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->addOption(
                'conf',
                null,
                InputOption::VALUE_OPTIONAL,
                'The configuration.php file path. Example /var/www/chamilo/config/configuration.php'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'For tests'
            );
    }

    /**
     * Get connection to the database
     * @return \Doctrine\DBAL\Connection
     *
     */
    public function getConnection()
    {
        try {
            return $this->getHelper('db')->getConnection();
        } catch (Exception $e)  {
            echo $e->getMessage();
        }
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        try {
            return $this->getHelper('em')->getEntityManager();
        } catch (Exception $e)  {
            echo $e->getMessage();
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configurationFile = $input->getOption('conf');
        $this->getConfigurationHelper()->setDryRun($input->getOption('dry-run'));
        $configuration = $this->getConfigurationHelper()->readConfigurationFile($configurationFile);

        if (empty($configuration)) {
            // Test out a few possibilities.
            $configurationFile = $this->getConfigurationHelper()->getConfigurationFilePath();
            $configuration = $this->getConfigurationHelper()->readConfigurationFile($configurationFile);
            if (empty($configuration)) {
                $output->writeln('<error>The configuration file was not found or Chamilo is not installed here.</error>');
                $output->writeln('<comment>Try</comment> <info>prefix:command --conf=/var/www/chamilo/config/configuration.php</info>');
                exit;
            }
        }

        $this->setConfigurationArray($configuration);
        $this->getConfigurationHelper()->setConfiguration($configuration);

        $sysPath = $this->getConfigurationHelper()->getSysPathFromConfigurationFile($configurationFile);


        $this->getConfigurationHelper()->setSysPath($sysPath);

        $databaseSettings = array(
            'driver' => 'pdo_mysql',
            'host' => $configuration['db_host'],
            'dbname' => $configuration['main_database'],
            'user' => $configuration['db_user'],
            'password' => $configuration['db_password']
        );

        // Setting doctrine connection
        $this->setDatabaseSettings($databaseSettings);
        $this->setDoctrineSettings();
        //$this->getApplication()->getHelperSet()->set($this->getConfigurationHelper(), 'configuration');
    }
}
