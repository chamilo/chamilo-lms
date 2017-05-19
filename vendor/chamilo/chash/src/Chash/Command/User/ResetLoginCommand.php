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
 * Class ResetLoginCommand
 * Returns a password reset link for the given username (user will receive
 * an e-mail with new login + password)
 * @package Chash\Command\User
 */
class ResetLoginCommand extends CommonChamiloUserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:reset_login')
            ->setDescription('Outputs login link for given username')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Allows you to specify a username to login as'
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
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $connection = $this->getConnection($input);
        $username = $input->getArgument('username');
        $us = "SELECT * FROM user WHERE username = '".mysql_real_escape_string($username)."'";
        $uq = mysql_query($us);
        $un = mysql_num_rows($uq);
        if ($un >= 1) {
            $user = mysql_fetch_assoc($uq);
            $link = $_configuration['root_web'].'main/auth/lostPassword.php?reset='.md5($_configuration['security_key'].$user['email']).'&id='.$user['user_id'];
            $output->writeln('Follow this link to login as '.$username);
            $output->writeln($link);
        } else {
            $output->writeln('Could not find user '.$username);
        }
        return null;
    }
}
