<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Make a full backup of the given/current install and put the results
 * (files and db) into the given file.
 * Store the temporary data into the /tmp/ directory
 * @param array $params The params received
 */
class FullBackupCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this->setHelp(
            'The full_backup command allows you to do a full backup of the files and database of a given Chamilo installation'
        );

        $this
            ->setName('db:full_backup')
            ->setDescription('Generates a .tgz from the Chamilo files and database')
            ->addArgument(
                'result',
                InputArgument::REQUIRED,
                'Allows you to specify a destination file, e.g. database:full_backup /home/user/backup.tgz or backup.tgz'
            )
            ->addOption(
                'tmp',
                null,
                InputOption::VALUE_OPTIONAL,
                'Allows you to specify in which temporary directory the backup files should be placed (optional, defaults to /tmp)'
            )
            ->addOption(
                'del-archive',
                null,
                InputOption::VALUE_NONE,
                'Deletes the contents of the archive/ directory before the backup is executed'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $_configuration = $this->getConfigurationArray();
        $resultPath     = $input->getArgument('result');
        $tmpFolder      = $input->getOption('tmp');

        if (empty($tmpFolder)) {
            $output->writeln(
                '<info>No temporary directory defined. Assuming /tmp/. Please make sure you have *enough space* left on that device</info>'
            );
            $tmpFolder = '/tmp';
        }

        $deleteTemp = $input->getOption('del-archive');

        if ($deleteTemp) {
            //Calling command
            $command = $this->getApplication()->find('files:clean_temp_folder');

            $arguments = array(
                'command' => 'files:clean_temp_folder'
            );
            $input     = new ArrayInput($arguments);
            $command->run($input, $output);
        } else {
            $output->writeln('<comment>Temp archives are not removed</comment>');
        }

        $cha_dir = realpath($_configuration['root_sys']);

        $output->writeln('<comment>Starting full backup</comment>');

        $f = $_configuration['db_user'];
        //backup the files (this requires root permissions)
        $bkp_dir = $tmpFolder.'/'.$f.'-'.date('Ymdhis');
        $err     = @mkdir($bkp_dir);
        $tgz     = $bkp_dir.'/'.$f.'.tgz';
        $sql     = $bkp_dir.'/'.$f.'-db.sql';
        $err     = @system('tar zcf '.$tgz.' '.$cha_dir);

        $output->writeln('<comment>Generating mysqldump</comment>');

        $err = @system(
            'mysqldump -h '.$_configuration['db_host'].' -u '.$_configuration['db_user'].' -p'.$_configuration['db_password'].' '.$_configuration['main_database'].' --result-file='.$sql
        );

        $output->writeln('<comment>Generating tarball </comment>');

        $err = @system('tar zcf '.$resultPath.' '.$bkp_dir);
        $err = @system('rm -rf '.$bkp_dir);

        $output->writeln(
            '<comment>End Chamilo backup. File can be found here: '.realpath($resultPath).' </comment>'
        );

    }
}
