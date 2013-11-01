<?php

namespace ChamiloLMS\Command\Modulation;

use Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use \ChamiloSession as Session;
use \UserManager as UserManager;
use Entity;

/**
 * This command copies the current folder and the current database to a
 * duplication space, updating all the corresponding data
 *
 * Assumptions:
 * - you should launch this command as root
 * - the destination folder will be in the same root directory as your main install
 * - the database server is on localhost
 * - the Chamilo installation to be copied lies in /var/www
 */
class ModulationDuplicateInstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('modulation:duplicate-install')
            ->setDescription('Duplicates a Chamilo installation')
            ->addArgument('pass', InputArgument::REQUIRED, 'The root password for the database server')
            ->addArgument('dest', InputArgument::REQUIRED, 'The name of the destination folder and database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        die(); //not implemented yet
        // This is just wrong, but was requested. Used to pass a session_id to
        // be used on the MineduAuthHttpsPostSend plugin.
        global $session_id, $app;
        Session::setSession($app['session']);
        Session::write('_user', api_get_user_info(1));

        $rootPass = intval($input->getArgument('pass'));
        $destName = intval($input->getArgument('dest'));
        $dbhr = mysqli_connect('localhost','root',$rootPass);
        $r = mysqli_query($dbhr,"SHOW DATABASES LIKE '$destName'");
        if (mysqli_num_rows($r) > 0) {
            $output->writeln('A database with this name already exists. Please choose another dest.');
            return 0;
        }

        if (is_dir('/var/www/'.$destName)) {
            $output->writeln('A folder with this name already exists. Please choose another dest.');
            return 0;
        }


        $output->writeln('The database should now be isolated.');
        return true;
    }
}
