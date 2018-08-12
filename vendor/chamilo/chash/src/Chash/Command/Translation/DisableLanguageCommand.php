<?php

namespace Chash\Command\Translation;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DisableLanguageCommand
 * Definition of the translation:disable command
 * Disable a language. Does not support multi-url yet
 * @package Chash\Command\Translation
 */
class DisableLanguageCommand extends CommonDatabaseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:disable')
            ->setAliases(array('tdl'))
            ->setDescription('Disables a (enabled) language')
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                'The English name for the language to disable.'
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
        $lang = mysql_real_escape_string($input->getArgument('language'));
        $ls = "SELECT id, english_name, available FROM language WHERE english_name = '$lang'";
        $lq = mysql_query($ls);
        if ($lq === false) {
            $output->writeln('Error in query: '.mysql_error());
            return null;
        }
        $num = mysql_num_rows($lq);
        if ($num<1) {
            $output->writeln($lang.' language not found in the database. Please make sure you use an existing language name.');
            return null;
        }
        $lr = mysql_fetch_assoc($lq);
        if ($lr['available'] == 0) {
            $output->writeln($lang.' language is already disabled. Nothing to do.');
            return null;
        }
        // Everything is OK so far, enable the language
        $us = "UPDATE language SET available = 0 WHERE id = {$lr['id']}";
        $uq = mysql_query($us);
        if ($uq === false) {
            $output->writeln('Error in query: '.mysql_error());
        } else {
            $output->writeln($lang . ' language has been disabled.');
        }
        return null;
    }
}
