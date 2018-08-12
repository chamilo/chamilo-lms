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
 * Class ChangePassCommand
 * Changes a user password to the one given
 * @package Chash\Command\User
 */
class ChangePassCommand extends CommonChamiloUserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:change_pass')
            ->setDescription('Updates the user password to the one given')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Allows you to specify the username'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'The new password to give this user'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $connection = $this->getConnection($input);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $us = "SELECT * FROM user WHERE username = '".mysql_real_escape_string($username)."'";
        $uq = mysql_query($us);
        $un = mysql_num_rows($uq);
        if ($un >= 1) {
            $enc = $_configuration['password_encryption'];
            switch ($enc) {
                case 'sha1':
                    $password = sha1($password);
                    break;
                case 'md5':
                    $password = md5($password);
                    break;
                default:
                    $password = mysql_real_escape_string($password);
                    break;
            }
            $user = mysql_fetch_assoc($uq);
            $ups = "UPDATE user SET password = '$password' WHERE user_id = ".$user['user_id'];
            $upq = mysql_query($ups);
            $output->writeln('User '.$username.' has new password.');
        } else {
            $output->writeln('Could not find user '.$username);
        }
        return null;
    }
}
