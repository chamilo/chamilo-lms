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
 * Remove the "admin" role from *ALL* users on all portals of this instance
 */
class DisableAdminsCommand extends CommonChamiloUserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:disable_admins')
            ->setDescription('Makes the given user admin on the main portal');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $dbh = $this->getHelper('configuration')->getConnection();
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
