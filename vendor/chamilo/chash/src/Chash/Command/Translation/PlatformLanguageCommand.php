<?php
/**
 * Definition of command to
 * change platform language
 * Does not support multi-url yet
 */
/**
 * Necessary namespaces definitions and usage
 */
namespace Chash\Command\Translation;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PlatformLanguageCommand
 * Definition of the translation:platform_language command
 * @package Chash\Command\Translation
 */
class PlatformLanguageCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:platform_language')
            ->setAliases(array('tpl'))
            ->setDescription('Gets or sets the platform language')
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                'Which language you want to set (English name). Leave empty to get current language.'
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
        if (empty($lang)) {
            $ls = "SELECT selected_value FROM settings_current WHERE variable='platformLanguage'";
            $lq = mysql_query($ls);
            if ($lq === false) {
                $output->writeln('Error in query: '.mysql_error());
                return null;
            } else {
                $lr = mysql_fetch_assoc($lq);
                $output->writeln('Current default language is: '.$lr['selected_value']);
            }
        } else {
            $ls = "SELECT english_name FROM language ORDER BY english_name";
            $lq = mysql_query($ls);
            if ($lq === false) {
                $output->writeln('Error in query: '.mysql_error());
                return null;
            } else {
                $languages = array();
                while ($lr = mysql_fetch_assoc($lq)) {
                    $languages[] = $lr['english_name'];
                }
                if (!in_array($lang,$languages)) {
                    $output->writeln($lang.' must be available on your platform before you can set it as default');
                    return null;
                }
                $lu = "UPDATE settings_current set selected_value = '$lang' WHERE variable = 'platformLanguage'";
                $lq = mysql_query($lu);
                if ($lq === false) {
                    $output->writeln('Error in query: '.mysql_error());
                } else {
                    $output->writeln('Language set to '.$lang);
                }
            }
        }
        return null;
    }
}
