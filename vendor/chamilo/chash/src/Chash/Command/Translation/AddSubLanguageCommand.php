<?php

namespace Chash\Command\Translation;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddSubLanguageCommand
 * Definition of the translation:add_sub_language command
 * Does not support multi-url yet
 * @package Chash\Command\Translation
 */
class AddSubLanguageCommand extends CommonDatabaseCommand
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
        $_configuration = $this->getConfigurationArray();
        $connection = $this->getConnection($input);

        $parent = $input->getArgument('parent');
        $lang = $input->getArgument('sublanguage');
        $sql = "SELECT english_name FROM language WHERE english_name = ?";
        $statement = $connection->executeQuery($sql, array($lang));
        $count = $statement->rowCount();

        if ($count) {
            $output->writeln($lang.' already exists in the database. Pick another English name.');
            return null;
        }

        $sql = "SELECT id, original_name, english_name, isocode, dokeos_folder
                FROM language WHERE english_name = ?";
        $statement = $connection->prepare($sql);
        $statement->bindValue('1', $parent);
        $statement->execute();
        $count = $statement->rowCount();
        $parentData = $statement->fetch();

        if ($count < 1) {
            $output->writeln('The parent language '.$parent.' does not exist. Please choose a valid parent.');
            return null;
        }

        if (is_dir($_configuration['root_sys'].'main/lang/'.$lang)) {
            $output->writeln('The destination directory ('.$_configuration['root_sys'].'main/lang/'.$lang.') already exists. Please choose another sub-language name.');
            return null;
        }

        // Everything is OK so far, insert the sub-language
        /*$sql = "INSERT INTO language ()
               VALUES ('{$parentData['original_name']}-2','$lang','{$parentData['isocode']}','$lang',0,{$parentData['id']})";*/
        $result = $connection->insert('language', array(
                'original_name' => $parentData['original_name']."-2",
                'english_name' => $lang,
                'isocode' => $parentData['isocode'],
                'dokeos_folder' => $lang,
                'available' => 0,
                'parent_id' => $parentData['id']
            )
        );

        if ($result) {
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
