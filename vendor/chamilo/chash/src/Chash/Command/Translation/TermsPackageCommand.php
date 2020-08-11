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
 *
 * TermsPackage command, made to simplify the live of
 * translators by providing them with a list of the 10,000 most used words in
 * their own language to make sure they can have a strong first impact.
 * To make this work, you will have to have the original 10,000 most used words
 * in English. You can either get them by sending an e-mail to ywarnier@chamilo.org
 * or by starting the langstats scripts (check main/cron/lang/ and
 * main/inc/global.inc.php ~600), then collecting the variables with the scripts
 * in main/cron/lang/
 * The present command serves only at the end of this process, to generate the
 * corresponding language packages in other languages than English
 * @package Chash\Command\Translation
 */
class TermsPackageCommand extends CommonDatabaseCommand
{
    /**
     * Set the input variables and what will be shown in command helper
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('translation:terms_package')
            ->setDescription('Generates a package of given language terms')
            //(provided in English), in a specific destination language. It requires a Chamilo installation to work as it needs the existing main/lang/ folder to produce the destination language files with as much data as possible.')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'The directory containing the reference files and terms, in English'
            )
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                'The language in which you want the package of files and terms'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'The directory in which you want the package files to be put'
            )
            ->addOption(
                'tgz',
                null,
                InputOption::VALUE_NONE,
                'Add this option to compress the files (including the directories and the original English form) into one .tar.gz file ready for shipping'
            )
            ->addOption(
                'new',
                null,
                InputOption::VALUE_NONE,
                'Allow new languages (languages that do not exist yet). This will generate empty (but usable) translation files.'
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

        $source  = $input->getArgument('source');
        $language  = $input->getArgument('language');
        $destination = $input->getArgument('dest');
        $tgz = $input->getOption('tgz');
        $allowNew = $input->getOption('new');

        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $baseDir = $_configuration['root_sys'];
        if (substr($baseDir,-1,1) != '/') {
            $baseDir .= '/';
        }
        if (substr($source,-1,1) != '/') {
            $source .= '/';
        }
        if (substr($destination,-1,1) != '/') {
            $destination .= '/';
        }

        if (!is_dir($source)) {
            $output->writeln('The directory '.$source.' does not seem to exist. The source directory must exist and contain the language files, similar to e.g. /var/www/chamilo/main/lang/english');
            exit;
        }
        // Generate a folder name for saving the *partial* files in the original language - use suffix "_partial
        $origLang = substr(substr($source,0,-1),strrpos(substr($source,0,-1),'/')).'_partial';

        if (!is_dir($destination)) {
            $output->writeln('The directory '.$destination.' does not seem to exist. The destination directory must exist in order for this script to write the results in a safe place');
            exit;
        }
        if (!is_writeable($destination)) {
            $output->writeln('The destination directory must be writeable. '.$destination.' seems not to be writeable now.');
            exit;
        }
        if (empty($language)) {
            $output->writeln('The destination language must be provided for this script to work. Received '.$language.', which could not be identified.');
            exit;
        }
        $langDir = $baseDir.'main/lang/';
        $listDir = scandir($langDir);
        $langs = array();
        foreach ($listDir as $lang) {
            if (substr($lang,0,1) == '.') { continue; }
            if (!is_dir($langDir.$lang)) { continue; }
            $langs[] = $lang;
        }
        $new = false;
        if (!in_array($language, $langs)) {
            if (!$allowNew) {
                $output->writeln('The destination language must be expressed as one of the directories available in your Chamilo installation. If you are exporting for the creation of a new language, use the --new option to ignore this warning');
                exit;
            } else {
                $new = true;
            }
        }
        if (is_dir($destination.$language)) {
            if (!is_writeable($destination.$language)) {
                $output->writeln('Destination directory '.$destination.$language.' already exists but is not writeable. Please make sure whoever launches this script has privileges to write in there.');
                exit;
            }
            $output->writeln('Destination directory '.$destination.$language.' already exists. We recommend using an empty directory. Files in this directory will be overwritten if necessary. Sorry.');
        } elseif (!@mkdir($destination.$language)) {
            $output->writeln('For some reason, the directory creation returned an error for '.$destination.$language);
            exit;
        }
        if (is_dir($destination.$origLang)) {
            if (!is_writeable($destination.$origLang)) {
                $output->writeln('Destination directory '.$destination.$origLang.' already exists but is not writeable. Please make sure whoever launches this script has privileges to write in there.');
                exit;
            }
            $output->writeln('Destination directory '.$destination.$origLang.' already exists. We recommend using an empty directory. Files in this directory will be overwritten if necessary. Sorry.');
        } elseif (!@mkdir($destination.$origLang)) {
            $output->writeln('For some reason, the directory creation returned an error for '.$destination.$origLang);
            exit;
        }
        // Start working on those files!
        $listFiles = scandir($source);
        $countVars = 0;
        $countTranslatedVars = 0;
        $countWords = 0;
        $countTranslatedWords = 0;
        $fileString = '<?php'."\n";
        foreach ($listFiles as $file) {
            if (substr($file,-1,1) == '.') { continue; }
            $destFileLines = $fileString;
            $origFileLines = $fileString;
            $partialSourceFile = $langDir.$language.'/'.$file;
            $output->writeln('Source File 2 = '.$partialSourceFile);
            $sourceVars = $this->_getLangVars($source.$file);
            $source2Vars = array();
            if (is_file($partialSourceFile)) {
                $source2Vars = $this->_getLangVars($partialSourceFile);
            }
            $source2Keys = array_keys($source2Vars);
            foreach ($sourceVars as $var => $val) {
                if (in_array($var, $source2Keys)) {
                    $destFileLines .= '$'.$var.'='.$source2Vars[$var]."\n";
                    $origFileLines .= '$'.$var.'='.$val."\n";
                    $countTranslatedVars++;
                    $countTranslatedWords += str_word_count($sourceVars[$var]);
                } else {
                    $destFileLines .= '$'.$var.'="";'."\n";
                    $origFileLines .= '$'.$var.'='.$val."\n";
                }
                $countVars++;
                $countWords += str_word_count($sourceVars[$var]);
            }
            $output->writeln('Writing to file '.$destination.$language.'/'.$file);
            $w = file_put_contents($destination.$language.'/'.$file, $destFileLines);
            $w = file_put_contents($destination.$origLang.'/'.$file, $origFileLines);
        }
        $output->writeln('Written translation files for packaging in '.$destination.$language.'.');
        $output->writeln('Found '.$countVars.' variables, of which '.$countTranslatedVars.' were already translated (and '.($countVars-$countTranslatedVars).' are missing).');
        $output->writeln('In words, there are '.$countWords.' words in total, of which only '.($countWords - $countTranslatedWords).' still need translating.');
        if ($tgz) {
            $output->writeln('Compressing as .tar.gz...');
            chdir($destination);
            exec('tar zcf '.$destination.$language.'.tar.gz '.$language);
            $output->writeln('Written to '.$destination.$language.'.tar.gz');
            $output->writeln('Removing work directory '.$destination.$language);
            exec('rm -rf '.$destination.$language);
        }
        $output->writeln('Finished exporting language package.');
        if (!$tgz) {
            $output->writeln('Please make sure you review your work directory for possible cleaning.');
        }
    }

    /**
     * Gets all the variables in a language file as a hash
     * This is a copy of the get_all_language_variable_in_file method in main/admin/sub_language.class.php
     * @param string $file The asbolute path to the file from which to extract variables
     * @return array Named array of variable => translation
     */
    private function _getLangVars($file) {
        $res_list = array();
        if (!is_readable($file)) {
            return $res_list;
        }
        $info_file = file($file);
        foreach ($info_file as $line) {
            if (substr($line, 0, 1) != '$') {
                continue;
            }
            list($var, $val) = preg_split('/=/', $line, 2);
            $var = trim($var);
            $val = trim($val);
            //remove the $ prefix
            $var = substr($var, 1);
            $res_list[$var] = $val;
        }
        return $res_list;
    }
}
