<?php
/* For licensing terms, see /license.txt */
/**
 * Script to check that no language file has parse errors.
 *
 * @package chamilo.cron.lang
 */
/**
 * Includes and declarations.
 */
//die();
require_once '../../inc/global.inc.php';
$path = api_get_path(SYS_LANG_PATH).'english';
ini_set('memory_limit', '128M');
/**
 * Main code.
 */
$terms = [];
$list = SubLanguageManager::get_lang_folder_files_list($path);
$langs = scandir(api_get_path(SYS_LANG_PATH));
foreach ($langs as $lang) {
    $dir = api_get_path(SYS_LANG_PATH).$lang;
    if (is_dir($dir) && substr($lang, 0, 1) != '.' && !empty($lang)) {
        echo "$lang...";
        $ok = true;
        foreach ($list as $entry) {
            $file = $dir.'/'.$entry;
            $out = [];
            if (is_file($file)) {
                //$terms = array_merge($terms,SubLanguageManager::get_all_language_variable_in_file($file,true));
                @exec('php -l '.$file, $out);
                if (substr($out[0], 0, 2) != 'No') {
                    echo $out[0]."\n";
                    $ok = false;
                }
            }
        }
        if ($ok) {
            echo "OK\n";
        }
    }
}
echo "Done\n";
