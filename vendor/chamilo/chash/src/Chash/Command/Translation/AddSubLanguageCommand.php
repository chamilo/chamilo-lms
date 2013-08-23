<?php
/**
 * Definition of command to
 * add sub language
 * Does not support multi-url yet
 */
/**
 * Necessary namespaces definitions and usage
 */
namespace Chash\Command\Translation;

use Chash\Command\Database\CommonChamiloDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddSubLanguageCommand
 * Definition of the translation:add_sub_language command
 * @package Chash\Command\Translation
 */
class AddSubLanguageCommand extends CommonChamiloDatabaseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:add_sub_language')
            ->setAliases(array('tasl'))
            ->setDescription('Creates a sub-language')
            ->addArgument(
                'parent',
                InputArgument::REQUIRED,
                'The parent language (English name) for the new sub-language.'
            )
            ->addArgument(
                'sublanguage',
                InputArgument::REQUIRED,
                'The English name for the new sub-language.'
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
        $dbh = $this->getHelper('configuration')->getConnection();
        $parent = mysql_real_escape_string($input->getArgument('parent'));
        $lang = mysql_real_escape_string($input->getArgument('sublanguage'));
        $ls = "SELECT english_name FROM language WHERE english_name = '$lang'";
        $lq = mysql_query($ls);
        if ($lq === false) {
            $output->writeln('Error in query: '.mysql_error());
            return null;
        }
        $num = mysql_num_rows($lq);
        if ($num>0) {
            $output->writeln($lang.' already exists in the database. Pick another English name.');
            return null;
        }
        $ls = "SELECT id, original_name, english_name, isocode, dokeos_folder FROM language WHERE english_name = '$parent'";
        $lq = mysql_query($ls);
        if ($lq === false) {
            $output->writeln('Error in query: '.mysql_error());
            return null;
        }
        $num = mysql_num_rows($lq);
        if ($num<1) {
            $output->writeln('The parent language '.$parent.' does not exist. Please choose a valid parent.');
            return null;
        }
        if (is_dir($_configuration['root_sys'].'main/lang/'.$lang)) {
            $output->writeln('The destination directory ('.$_configuration['root_sys'].'main/lang/'.$lang.') already exists. Please choose another sub-language name.');
            return null;
        }
        // Everything is OK so far, insert the sub-language
        $lr = mysql_fetch_assoc($lq);
        $is = "INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available, parent_id) VALUES ('{$lr['original_name']}-2','$lang','{$lr['isocode']}','$lang',0,{$lr['id']})";
        $iq = mysql_query($is);
        if ($iq === false) {
            $output->writeln('Error in query: '.mysql_error());
        } else {
            //permissions gathering, copied from main_api.lib.php::api_get_permissions_for_new_directories()
            //require_once $_configuration['root_sys'].'main/inc/lib/main_api.lib.php';
            //$perm = api_get_permissions_for_new_directories();
            // @todo Improve permissions to force creating as user www-data
            $r = @mkdir($_configuration['root_sys'].'main/lang/'.$lang, 0777);
            $output->writeln('Sub-language '.$lang.' of language '.$parent.' has been created but is disabled. Fill it, then enable to make available to users. Make sure you check the permissions for the newly created directory as well ('.$_configuration['root_sys'].'main/lang/'.$lang.')');
        }
        return null;
    }
}
