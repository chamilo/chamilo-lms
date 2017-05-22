<?php /* For licensing terms, see /license.txt */
/**
 * Generate a file with the undefined terms of one language and another file with the existing English terms.
 * Copy-paste the resulting page in an Excel spreasheet to have it ready to go for translators
 * @package chamilo.cron
 */
/**
 * Includes and declarations
 */
die();
require_once '../../main/inc/global.inc.php';
$path = api_get_path(SYS_LANG_PATH);
$referenceLanguage = 'english';
$language = 'german';
ini_set('memory_limit','128M');
/**
 * Main code
 */
$referenceTerms = array();
$file = $path . $referenceLanguage . '/trad4all.inc.php';
if (is_file($file)) {
    $referenceTerms = array_merge($referenceTerms, SubLanguageManager::get_all_language_variable_in_file($file,true));
}
// get only the array keys (the language variables defined in language files)
$definedTerms = array_keys($referenceTerms);
//print_r($definedTerms);
//$referenceTerms = null;

// now get all terms found in the destination language files of Chamilo (this takes some time and memory)
$missingTerms = array();
$nonMissingTerms = array();
$l = strlen(api_get_path(SYS_PATH));
$file = $path . $language . '/trad4all.inc.php';
if (is_file($file)) {
    $nonMissingTerms = array_merge($nonMissingTerms, SubLanguageManager::get_all_language_variable_in_file($file,true));
}
$nonMissingTerms = array_keys($nonMissingTerms);
//print_r($nonMissingTerms);

$missingTerms = array_diff($definedTerms, $nonMissingTerms);
//print_r($missingTerms);

echo "<table border='1'>\n";
echo "<tr><th>Count</th><th>Term</th><th>English</th><th>German</th></tr>";
$i = 1;
$countWords = 0;
foreach ($missingTerms as $key => $term) {
    if (isset($referenceTerms[$term])) {
        $trimmed = trim($referenceTerms[$term],';" ');
        $countWords += str_word_count($trimmed);
        echo "<tr><td>$i</td><td>$term</td><td>".$trimmed."</td><td></td></tr>\n";
    }
    $i++;
}
echo "</table>\n";
echo "Total words to be translated: ".$countWords.PHP_EOL;
