<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Command\Translation;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ExportLanguagesCommand
 * @package ChamiloLMS\Command\Translation
 */
class ExportLanguagesCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:export_to_gettext')
            ->setDescription('Export chamilo translations to po files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $languageList = array('english','spanish','french');
        /** @var \Silex\Application $app */
        /*
        $app = $this->getApplication()->getSilexApplication();
        $tempPath = $app['paths']['sys_root'].'main/locale';
        $l = scandir($app['paths']['sys_root'].'main/lang');
        foreach ($l as $item) {
            if (substr($item,0,1) == '.') { continue; }
            $languageList[] = $item;
        }
        */
        foreach ($languageList as $lang) {
            $output->writeln("<info>Generating lang po files for: $lang</info>");
            $this->convertLanguageToGettext($lang, $output);
        }
    }

    /**
     * @param string $destinationLanguage Chamilo language 'spanish', 'french', etc
     * @param OutputInterface $output
     */
    private function convertLanguageToGettext($destinationLanguage, OutputInterface $output)
    {
        /** @var \Silex\Application $app */
        $app = $this->getApplication()->getSilexApplication();
        //$tempPath = $app['paths']['sys_temp_path'].'langs';

        $tempPath = $app['paths']['sys_root'].'main/locale';

        if (!is_dir($tempPath)) {
            mkdir($tempPath);
            $output->writeln('<info>folder $tempPath created </info>');
        }

        $isocode = api_get_language_isocode($destinationLanguage);

        $englishPath = $app['paths']['root_sys'].'main/lang/english/';

        // Translate this language.
        $toLanguagePath = $app['paths']['root_sys'].'main/lang/'.$destinationLanguage;

        if (is_dir($englishPath)) {
            if ($dh = opendir($englishPath)) {

                $new_po_file = $tempPath.'/'.$isocode.'.po';
                $fp = fopen($new_po_file, 'w');

                $header = 'msgid ""'."\n".'msgstr ""'."\n".'"Content-Type: text/plain; charset=utf-8 \n"';
                fwrite($fp, $header);

                while (($file = readdir($dh)) !== false) {
                    $info = pathinfo($file);
                    if ($info['extension'] != 'php') {
                        continue;
                    }
                    $info['filename'] = explode('.', $info['filename']);
                    $info['filename'] = $info['filename'][0];

                    if ($info['filename'] != 'admin') {
                        //continue;
                    }

                    $translations = array();
                    $filename = $englishPath.'/'.$file;
                    $po = file($filename);
                    if (!file_exists($filename) || !file_exists($toLanguagePath.'/'.$file)) {
                        continue;
                    }

                    foreach ($po as $line) {
                        $pos = strpos($line, '=');
                        if ($pos) {
                            // Get the variable name (part before the = sign, without $)
                            $variable = (substr($line, 1, $pos-1));
                            $variable = trim($variable);

                            //require $filename;
                            //if (isset($$variable)) {
                            //    $my_variable_in_english = $$variable;
                            if (file_exists($toLanguagePath.'/'.$file)) {
                                require $toLanguagePath.'/'.$file;
                                /** Fixes a notice due to array in the lang files */
                                if (strpos($variable, 'langNameOfLang') === false && isset($$variable)) {
                                    // \r\n change tries to avoid CRLF issue
                                    // related to https://bugs.php.net/bug.php?id=52671
                                    $translations[] = array(
                                        'msgid'  => $variable,
                                        'msgstr' => $$variable
                                    );
                                } else {
                                    continue;
                                }
                            }
                        }
                    }

                    /*if (!is_dir($tempPath.'/'.$info['filename'])) {
                        mkdir($tempPath.'/'.$info['filename']);
                        $output->writeln('<info>folder '.$tempPath.'/'.$info['filename'].' created </info>');
                    }*/

                    fwrite($fp, "\n\n");
                    foreach ($translations as $item) {
                        $line = 'msgid "'.addslashes($item['msgid']).'"'."\n";

                        $translated = $item['msgstr'];
                        $translated = addslashes($translated);
                        $translated = str_replace(array("\\'"), "'", $translated);
                        $translated = str_replace(array("\n"), '\n', $translated);
                        $line .= 'msgstr "'.$translated.'"'."\n\n";
                        fwrite($fp, $line);
                    }

                    $message = "File $file parsed ".$new_po_file;
                    $output->writeln($message);

                }
                fclose($fp);
                closedir($dh);
            }
        }
    }

    /**
     * Converts the classic chamilo lang into folders example: locale/trad4all/en.po, locale/trad4all/es.po, etc
     * @param string $destinationLanguage Chamilo language 'spanish', 'french', etc
     * @param OutputInterface $output
     */
    private function convertLanguageToGettextDivideInFolders($destinationLanguage, OutputInterface $output)
    {
        /** @var \Silex\Application $app */
        $app = $this->getApplication()->getSilexApplication();
        //$tempPath = $app['paths']['sys_temp_path'].'langs';

        $tempPath = $app['paths']['sys_root'].'main/locale';

        if (!is_dir($tempPath)) {
            mkdir($tempPath);
            $output->writeln('<info>folder $tempPath created </info>');
        }

        $isocode = api_get_language_isocode($destinationLanguage);

        $englishPath = $app['paths']['root_sys'].'main/lang/english/';

        // Translate this language.
        $toLanguagePath = $app['paths']['root_sys'].'main/lang/'.$destinationLanguage;

        if (is_dir($englishPath)) {
            if ($dh = opendir($englishPath)) {
                while (($file = readdir($dh)) !== false) {
                    $info = pathinfo($file);
                    if ($info['extension'] != 'php') {
                        continue;
                    }
                    $info['filename'] = explode('.', $info['filename']);
                    $info['filename'] = $info['filename'][0];

                    if ($info['filename'] != 'admin') {
                        //continue;
                    }

                    $translations = array();
                    $filename = $englishPath.'/'.$file;
                    $po = file($filename);
                    if (!file_exists($filename) || !file_exists($toLanguagePath.'/'.$file)) {
                        continue;
                    }

                    foreach ($po as $line) {
                        $pos = strpos($line, '=');
                        if ($pos) {
                            $variable = (substr($line, 1, $pos-1));
                            $variable = trim($variable);

                            require $filename;
                            $my_variable_in_english = $$variable;
                            require $toLanguagePath.'/'.$file;
                            $my_variable = $$variable;
                            /** This fixes a notice due an array in the lang files */
                            if (strpos($variable, 'langNameOfLang') === false) {

                                $translations[] = array(
                                    'msgid'  => $variable,
                                    'msgstr' => $my_variable
                                );
                            } else {
                                continue;
                            }
                        }
                    }

                    if (!is_dir($tempPath.'/'.$info['filename'])) {
                        mkdir($tempPath.'/'.$info['filename']);
                        $output->writeln('<info>folder '.$tempPath.'/'.$info['filename'].' created </info>');
                    }

                    $new_po_file = $tempPath.'/'.$info['filename'].'/'.$isocode.'.po';
                    $fp = fopen($new_po_file, 'w');
                    $header = 'msgid ""'."\n".'msgstr ""'."\n".'"Content-Type: text/plain; charset=utf-8 \n"';
                    fwrite($fp, $header);
                    fwrite($fp, "\n\n");
                    foreach ($translations as $item) {
                        $line = 'msgid "'.addslashes($item['msgid']).'"'."\n";

                        $translated = $item['msgstr'];
                        $translated = addslashes($translated);
                        $translated = str_replace(array("\\'"), "'", $translated);
                        $line .= 'msgstr "'.$translated.'"'."\n\n";
                        fwrite($fp, $line);
                    }
                    fclose($fp);

                    $message = "File $file converted to ".$new_po_file;
                    $output->writeln($message);

                }
                closedir($dh);
            }
        }
    }
}
