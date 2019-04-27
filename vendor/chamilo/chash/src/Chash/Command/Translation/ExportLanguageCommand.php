<?php

namespace Chash\Command\Translation;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportLanguageCommand
 * @package Chash\Command\Translation
 */
class ExportLanguageCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:export_language')
            ->setDescription('Exports a Chamilo language package')
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                'Which language you want to export'
            )
            ->addOption(
                'tmp',
                null,
                InputOption::VALUE_OPTIONAL,
                'Allows you to specify in which temporary directory the backup files should be placed (optional, defaults to /tmp)'
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

        $language  = $input->getArgument('language');
        $tmpFolder = $input->getOption('tmp');

        $_configuration = $this->getHelper('configuration')->getConfiguration();

        $connection = $this->getConnection($input);

        if ($connection) {
            $lang = isset($language) ? $language : null;

            $lang = mysql_real_escape_string($lang);

            $q        = mysql_query("SELECT * FROM language WHERE english_name = '$lang' ");
            $langInfo = mysql_fetch_array($q, MYSQL_ASSOC);

            if (!$langInfo) {

                $output->writeln("<comment>Language '$lang' is not registered in the Chamilo Database</comment>");

                $q = mysql_query("SELECT * FROM language WHERE parent_id IS NULL or parent_id = 0");
                $output->writeln("<comment>Available languages are: </comment>");
                while ($langRow = mysql_fetch_array($q, MYSQL_ASSOC)) {
                    $output->write($langRow['english_name'].", ");
                }
                $output->writeln(' ');

                $q = mysql_query("SELECT * FROM language WHERE parent_id <> 0");
                $output->writeln("<comment>Available sub languages are: </comment>");
                while ($langRow = mysql_fetch_array($q, MYSQL_ASSOC)) {
                    $output->write($langRow['english_name'].", ");
                }
                $output->writeln(' ');
                exit;
            } else {
                $output->writeln(
                    "<comment>Language</comment> <info>'$lang'</info> <comment>is registered in the Chamilo installation with iso code: </comment><info>{$langInfo['isocode']} </info>"
                );
            }

            $langFolder = $_configuration['root_sys'].'main/lang/'.$lang;

            if (!is_dir($langFolder)) {
                $output->writeln("<comment>Language '$lang' does not exist in the path: $langFolder</comment>");
            }

            if (empty($tmpFolder)) {
                $tmpFolder = '/tmp/';
                $output->writeln(
                    "<comment>No temporary directory defined. Assuming /tmp/. Please make sure you have *enough space* left on that device"
                );
            }

            if (!is_dir($tmpFolder)) {
                $output->writeln(
                    "<comment>Temporary directory: $tmpFolder is not a valid dir path, using /tmp/ </comment>"
                );
                $tmpFolder = '/tmp/';
            }

            if ($langInfo) {
                $output->writeln("<comment>Creating translation package</comment>");
                $fileName = $tmpFolder.$langInfo['english_name'].'.tar';
                $phar     = new \PharData($fileName);
                $phar->buildFromDirectory($langFolder);

                $phar->setMetadata($langInfo);
                $output->writeln("<comment>File created:</comment> <info>{$fileName}</info>");
            }
        }
    }
}
