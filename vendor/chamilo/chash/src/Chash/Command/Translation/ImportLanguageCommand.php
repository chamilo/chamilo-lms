<?php

namespace Chash\Command\Translation;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportLanguageCommand
 * @package Chash\Command\Translation
 */
class ImportLanguageCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:import_language')
            ->setDescription('Import a Chamilo language package')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Path of the language package'
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

        $dialog = $this->getHelperSet()->get('dialog');

        $_configuration = $this->getHelper('configuration')->getConfiguration();

        $file = $input->getArgument('file');

        $connection = $this->getConnection($input);

        if (is_file($file) && is_readable($file)) {
            $phar = new \PharData($file);
            if ($phar->hasMetadata()) {
                $langInfo = $phar->getMetadata();

                if ($connection) {
                    $q = mysql_query(
                        "SELECT * FROM language WHERE dokeos_folder = '{$langInfo['dokeos_folder']}' "
                    );
                    $langInfoFromDB = mysql_fetch_array($q, MYSQL_ASSOC);
                    $langFolderPath = $_configuration['root_sys'].'main/lang/'.$langInfoFromDB['dokeos_folder'];
                    if ($langInfoFromDB && $langFolderPath) {
                        //Overwrite lang files
                        if (!$dialog->askConfirmation(
                            $output,
                            '<question>The '.$langInfo['original_name'].' language already exists in Chamilo. Did you want to overwrite the contents? (y/N)</question>',
                            false
                        )
                        ) {
                            return;
                        }
                        if (is_writable($langFolderPath)) {
                            $output->writeln("Trying to save files here: $langFolderPath");
                            $phar->extractTo($langFolderPath, null, true); // extract all files
                            $output->writeln("Files were copied.");
                        } else {
                            $output->writeln(
                                "<error>Make sure that this folder $langFolderPath has writable permissions or execute the script with sudo </error>"
                            );
                        }
                    } else {
                        //Check if parent_id exists
                        $parentId = '';
                        if (!empty($langInfo['parent_id'])) {
                            $sql                = "select selected_value from settings_current where variable = 'allow_use_sub_language'";
                            $result             = mysql_query($sql);
                            $subLanguageSetting = mysql_fetch_array($result, MYSQL_ASSOC);
                            $subLanguageSetting = $subLanguageSetting['selected_value'];
                            if ($subLanguageSetting == 'true') {

                                $q                    = mysql_query(
                                    "SELECT * FROM language WHERE id = '{$langInfo['parent_id']}' "
                                );
                                $parentLangInfoFromDB = mysql_fetch_array($q, MYSQL_ASSOC);
                                if ($parentLangInfoFromDB) {
                                    $output->writeln(
                                        "Setting parent language: ".$parentLangInfoFromDB['original_name']
                                    );
                                    $parentId = $langInfo['parent_id'];
                                } else {
                                    $output->writeln(
                                        "The lang parent_id = {$langInfo['parent_id']} does not exist in Chamilo. Try to import first the parent language."
                                    );
                                    exit;
                                }
                            } else {
                                $output->writeln(
                                    "<comment>Please turn ON the sublanguage feature in this portal</comment>"
                                );
                                exit;
                            }
                        } else {
                            $output->writeln("Parent language was not provided");
                        }

                        $q = mysql_query(
                            "INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available, parent_id) VALUES (
                                '".$langInfo['original_name']."',
                                '".$langInfo['english_name']."',
                                '".$langInfo['isocode']."',
                                '".$langInfo['dokeos_folder']."',
                                '1',
                                '".$parentId."')"
                        );

                        if ($q) {
                            $output->writeln("Language inserted in the DB");
                            $langFolderPath = $_configuration['root_sys'].'main/lang/'.$langInfo['dokeos_folder'];
                            $phar->extractTo($langFolderPath, null, true); // extract all files
                            $output->writeln("<comment>Files were copied here $langFolderPath </comment>");
                        } else {
                            $output->writeln("An error ocurred while tring to create the language");
                        }

                    }
                }
            } else {
                $output->writeln("<comment>The file is not a valid Chamilo language package<comment>");
            }
        } else {
            $output->writeln("<comment>The file located in '$file' is not accessible<comment>");
        }
    }
}
