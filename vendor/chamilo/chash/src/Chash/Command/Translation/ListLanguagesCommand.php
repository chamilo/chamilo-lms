<?php

namespace Chash\Command\Translation;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListLanguagesCommand
 * Definition of the translation:list command
 * Definition of command to list platform languages
 * @package Chash\Command\Translation
 */
class ListLanguagesCommand extends CommonDatabaseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:list')
            ->setAliases(array('tl'))
            ->setDescription('Gets all languages as a list')
            ->addArgument(
                'availability',
                InputArgument::OPTIONAL,
                'Filter the availability we want (0 for disabled, 1 for enabled, empty for all).'
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
        $av = mysql_real_escape_string($input->getArgument('availability'));
        $current = 'english';
        $ls = "SELECT selected_value FROM settings_current WHERE variable='platformLanguage'";
        $lq = mysql_query($ls);
        if ($lq === false) {
            $output->writeln('Error in query: '.mysql_error());
            return null;
        } else {
            $lr = mysql_fetch_assoc($lq);
            //$output->writeln('Current default language is: '.$lr['selected_value']);
            $current = $lr['selected_value'];
        }
        $where = '';
        if ($av === '0') {
            $where = 'WHERE available = 0';
        } elseif ($av === '1') {
            $where = 'WHERE available = 1';
        }
        $ls = "SELECT english_name, available FROM language ".$where." ORDER BY english_name";
        $lq = mysql_query($ls);
        if ($lq === false) {
            $output->writeln('Error in query: '.mysql_error());
            return null;
        } else {
            $languages = array();
            $output->writeln("Language          | Enabled | Platform language");
            $output->writeln("-----------------------------------------------");
            while ($lr = mysql_fetch_assoc($lq)) {
                $pl = '';
                $l = strlen($lr['english_name']);
                if ($lr['english_name'] == $current) { $pl = '*'; }
                $output->writeln($lr['english_name'].str_repeat(' ',18-$l)."| ".$lr['available']."       | ".$pl);
            }
        }
        return null;
    }
}
