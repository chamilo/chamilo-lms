<?php

namespace Chash\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Command functions meant to deal with what the user of this script is calling
 * it for.
 */
/**
 * Makes the given user an admin on the main portal
 */
class MakeAdminCommand extends CommonChamiloUserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:make_admin')
            ->setDescription('Makes the given user admin on the main portal')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Allows you to specify a username to make admin'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $connection = $this->getConnection($input);
        $username = $input->getArgument('username');
        $us = "SELECT * FROM user WHERE username = '".mysql_real_escape_string($username)."'";
        $uq = mysql_query($us);
        $un = mysql_num_rows($uq);
        if ($un >= 1) {
            $user = mysql_fetch_assoc($uq);
            $as = "SELECT * FROM admin WHERE user_id = ".$user['user_id'];
            $aq = mysql_query($as);
            $an = mysql_num_rows($aq);
            if ($an < 1) {
                //$output->writeln('User '.$username.' is not an admin. Making him one.');
                $ms = "INSERT INTO admin (user_id) VALUES (".$user['user_id'].")";
                $mq = mysql_query($ms);
                if ($mq === false) {
                    $output->writeln('Error making '.$username.' an admin.');
                } else {
                    $output->writeln('User '.$username.' is now an admin.');
                }
            } else {
                $output->writeln('User '.$username.' is alreay an admin.');
            }
        } else {
            $output->writeln('Could not find user '.$username);
        }
        return null;
    }
}
