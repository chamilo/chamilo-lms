<?php

namespace Chash\Command\Chash;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Yaml\Dumper;

/**
 * Class StatusCommand
 */
class SetupCommand extends AbstractCommand
{
    public $migrationFile = null;

    protected function configure()
    {
        $this
            ->setName('chash:setup')
            ->setDescription('Setups the migration.yml')
            ->addOption('migration-yml-path', null, InputOption::VALUE_OPTIONAL, 'The path to the migration.yml file')
            ->addOption('migration-class-path', null, InputOption::VALUE_OPTIONAL, 'The path to the migration class');
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
        $path = $input->getOption('migration-yml-path');

        if (empty($path)) {
            $srcPath = realpath(__DIR__.'/../../../').'/Chash/Migrations/';
        } else {
            $srcPath  = $path;
        }

        $migrationClassPath = $input->getOption('migration-class-path');

        if (empty($migrationClassPath)) {
            $migrationClassPath =  $srcPath;
        }

        $migrations = array(
            'name' => 'Chamilo Migrations',
            'migrations_namespace' => 'Chash\Migrations',
            'table_name' => 'chamilo_migration_versions',
            'migrations_directory' => $migrationClassPath
        );

        // does not work because it need a callable function yml_emit
        /*$config = new \Zend\Config\Config($migrations, true);
        $writer = new \Zend\Config\Writer\Yaml();
        $writer->toFile($srcPath.'/Chash/Migrations/migrations.ypl', $config);*/
        $dumper = new Dumper();
        $yaml = $dumper->dump($migrations, 1);
        $file = $srcPath.'/migrations.yml';
        file_put_contents($file, $yaml);

        // migrations_directory
        $output->writeln("<comment>Chash migrations.yml saved: $file</comment>");
        $this->migrationFile = $file;
    }

    public function getMigrationFile()
    {
        return $this->migrationFile;
    }
}
