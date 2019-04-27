<?php

namespace Chash\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DisableAdminsCommand
 * Remove the "admin" role from *ALL* users on all portals of this instance
 * @package Chash\Command\User
 */
class DisableAdminsCommand extends CommonChamiloUserCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:disable_admins')
            ->setDescription('Makes the given user admin on the main portal');
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
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog->askConfirmation(
            $output,
            '<question>This action will make all admins normal teachers. Are you sure? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $us = "DELETE FROM admin";
        $uq = mysql_query($us);
        if ($uq === false) {
            $output->writeln('Could not delete admins.');
        } else {
            $output->writeln('All admins disabled.');
        }
        return null;
    }
}
