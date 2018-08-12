<?php
/**
 * Command functions meant to deal with what the user of this script is calling
 * it for.
 */
/**
 * Namespaces
 */
namespace Chash\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetLanguageCommand
 * Changes the language for all platform users
 * @package Chash\Command\User
 */
class SetLanguageCommand extends CommonChamiloUserCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:set_language')
            ->setAliases(array('usl'))
            ->setDescription('Sets the users language to the one given')
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                'The English name for the new language to set all users to'
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
            $ls = "SELECT DISTINCT language, count(*) as num FROM user GROUP BY 1 ORDER BY language";
            $lq = mysql_query($ls);
            if ($lq === false) {
                $output->writeln('Error in query: '.mysql_error());
                return null;
            } else {
                $output->writeln("Language\t| Number of users");
                while ($lr = mysql_fetch_assoc($lq)) {
                    $output->writeln($lr['language']."\t\t| ".$lr['num']);
                }
            }
        } else {
            // Check available languages
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
                    $output->writeln($lang.' must be available on your platform before you can use it');
                    return null;
                }
                $lu = "UPDATE user SET language = '$lang'";
                $lq = mysql_query($lu);
                if ($lq === false) {
                    $output->writeln('Error in query: '.mysql_error());
                } else {
                    $output->writeln('Language set to '.$lang.' for all users');
                }
            }
        }
        return null;
    }
}
