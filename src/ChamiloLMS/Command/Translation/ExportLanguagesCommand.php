<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Command\Translation;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

/**
 * Class ExportLanguagesCommand
 * @package ChamiloLMS\Command\Translation
 */
class ExportLanguagesCommand extends Command
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('translation:export_to_gettext')
            ->setDescription('Export chamilo translations to po files');
            //->addArgument('theme', InputArgument::OPTIONAL, 'The theme to dump, if none is set then all themes will be generated', null);
    }

    /**
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $languageList = array('spanish');
        foreach ($languageList as $lang) {
            $output->writeln('<info>Generating lang po files for $lang</info>');
            $this->convertLanguageToGettext($lang, $output);
        }
    }

    private function convertLanguageToGettext($destinationLanguage, $output)
    {
        /** @var \Silex\Application $app */
        $app = $this->getApplication()->getSilexApplication();
        $tempPath = $app['paths']['sys_temp_path'].'langs';

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
                            $translations[] = array('msgid' =>$my_variable_in_english, 'msgstr' =>$my_variable);
                        }
                    }

                    //var_dump($translations);
                    $info['filename'] = explode('.', $info['filename']);
                    $info['filename'] = $info['filename'][0];

                    if (!is_dir($tempPath.'/'.$info['filename'])) {
                        mkdir($tempPath.'/'.$info['filename']);
                        $output->writeln('<info>folder '.$tempPath.'/'.$info['filename'].' created </info>');
                    }

                    $new_po_file = $tempPath.'/'.$info['filename'].'/'.$isocode.'.po';
                    $fp = fopen($new_po_file, 'w');

                    foreach ($translations as $item) {
                        $line = 'msgid "'.addslashes($item['msgid']).'"'."\n";
                        $line .= 'msgstr "'.addslashes($item['msgstr']).'"'."\n\n";
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
