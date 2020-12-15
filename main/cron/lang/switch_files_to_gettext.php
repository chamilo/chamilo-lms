<?php
/* For licensing terms, see /license.txt */
/**
 * Script to switch all PHP files in Chamilo to a more Gettext-like syntax.
 *
 * @package chamilo.cron.lang
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
foreach ($terms as $index => $translation) {
    $terms[$index] = trim(rtrim($translation, ';'), '"');
}
// get only the array keys (the language variables defined in language files)
$defined_terms = array_flip(array_keys($terms));
echo count($defined_terms)." terms were found in language files".PHP_EOL;

// now get all terms found in all PHP files of Chamilo (this takes some
// time and memory)
$usedTerms = [];
$l = strlen(api_get_path(SYS_PATH));
$files = getAllPhpFiles(api_get_path(SYS_PATH));
$rootLength = strlen(api_get_path(SYS_PATH));
$countFiles = 0;
$countReplaces = 0;
// Browse files
foreach ($files as $file) {
    if (substr($file, $rootLength, 6) === 'vendor' || substr($file, $rootLength, 3) === 'web') {
        continue;
    }
    //echo 'Analyzing '.$file.PHP_EOL;
    $shortFile = substr($file, $l);
    //echo 'Analyzing '.$shortFile.PHP_EOL;
    $lines = file($file);
    // Browse lines inside file $file
    foreach ($lines as $line) {
        $myTerms = [];
        $res = preg_match_all('/get_lang\(([\'"](\\w*)[\'"])\)/m', $line, $myTerms);
        if ($res > 0) {
            foreach ($myTerms[2] as $term) {
                echo "Found term $term - ".print_r($myTerms, 1).PHP_EOL;
                if (substr($term, 0, 4) == 'lang') {
                    $term = substr($term, 4);
                }
                if (!empty($terms[$term])) {
                    $translation = $terms[$term];
                    $quotedTerm = $myTerms[1][0];
                    //echo "Would do sed -i \"s#$quotedTerm#'$translation'#g\" $file here\n";
                    system("sed -i \"s#$term#'$translation'#g\" $file");
                    $countReplaces++;
                }
            }
        } else {
            $res = 0;
            $res = preg_match_all('/\{\s*([\'"](\\w*)[\'"])\s*\|get_lang\}/m', $line, $myTerms);
            if ($res > 0) {
                foreach ($myTerms[2] as $term) {
                    echo "Found term $term".PHP_EOL;
                    if (substr($term, 0, 4) == 'lang') {
                        $term = substr($term, 4);
                    }
                    if (!empty($terms[$term])) {
                        $translation = $terms[$term];
                        $quotedTerm = $myTerms[1][0];
                        //echo "Would do sed -i \"s#$quotedTerm#'$translation'#g\" $file here\n";
                        system("sed -i \"s#$term#'$translation'#g\" $file");
                        $countReplaces++;
                    }
                }
            }
        }
    }
    $countFiles++;
    flush();
}

echo "Done analyzing $countFiles files, with $countReplaces replacements!\n";
