<?php
/* For licensing terms, see /license.txt */
/**
 * Cron script to list used, but undefined, language variables.
 *
 * @package chamilo.cron
 */
/**
 * Includes and declarations.
 */
exit();
require_once __DIR__.'/../../inc/global.inc.php';
$path = api_get_path(SYS_LANG_PATH).'english';
ini_set('memory_limit', '128M');
/**
 * Main code.
 */
$terms = [];
$list = SubLanguageManager::get_lang_folder_files_list($path);
foreach ($list as $entry) {
    $file = $path.'/'.$entry;
    if (is_file($file)) {
        $terms = array_merge($terms, SubLanguageManager::get_all_language_variable_in_file($file, true));
    }
}
// get only the array keys (the language variables defined in language files)
$defined_terms = array_flip(array_keys($terms));
$terms = null;
$hidePlugins = !empty($_GET['hidePlugins']);

// now get all terms found in all PHP files of Chamilo (this takes some time and memory)
$undefined_terms = [];
$l = strlen(api_get_path(SYS_PATH));
$files = getAllPhpFiles(api_get_path(SYS_PATH));
foreach ($files as $file) {
    $isPlugin = preg_match('#/plugin/#', $file);
    if ($isPlugin && $hidePlugins) {
        continue;
    }
    //echo 'Analyzing '.$file."<br />";
    $shortfile = substr($file, $l);
    $lines = file($file);
    foreach ($lines as $line) {
        $myterms = [];
        // Find terms but ignore those starting with ->get_lang(), which are
        // for plugins
        $res = preg_match_all('/(?<!-\>)get_lang\(\'(\\w*)\'\)/', $line, $myterms);
        if ($res > 0) {
            foreach ($myterms[1] as $term) {
                if (!isset($defined_terms[$term]) && !isset($defined_terms['lang'.$term])) {
                    $undefined_terms[$term] = $shortfile;
                    //echo "Undefined: $term<br />";
                }
            }
        }
        $res = 0;
        $res = preg_match_all('/\{[\'"](\\w*)[\'"]\|get_lang\}/', $line, $myterms);
        if ($res > 0) {
            foreach ($myterms[1] as $term) {
                if (!isset($defined_terms[$term]) && !isset($defined_terms['lang'.$term])) {
                    $undefined_terms[$term] = $shortfile;
                    //echo "Undefined: $term<br />";
                }
            }
        }
    }
    flush();
}
//$undefined_terms = array_flip($undefined_terms);
if (count($undefined_terms) < 1) {
    exit("No missing terms<br />\n");
} else {
    echo "The following terms were nowhere to be found: <br />\n<table>";
}
$i = 1;
foreach ($undefined_terms as $term => $file) {
    $isPlugin = substr($file, 0, 7) == 'plugin/';
    echo "<tr><td>$i</td><td>$term</td><td>in $file";
    if ($isPlugin) {
        echo " <span style=\"color: #00ff00;\">(this one should be taken care of by the plugin's language files)</span>";
    }
    echo "</td></tr>\n";
    $i++;
}
echo "</table>\n";
