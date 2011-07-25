<?php /* For licensing terms, see /license.txt */
/**
 * Cron script to list used, but undefined, language variables
 * @package chamilo.cron
 */
/**
 * Includes and declarations
 */
//if (PHP_SAPI!='cli') { die('Run this script through the command line or comment this line in the code'); }
require_once '../../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php';
$path = api_get_path(SYS_LANG_PATH).'english';
ini_set('memory_limit','128M');
/**
 * Main code
 */
$terms = array();
$list = SubLanguageManager::get_lang_folder_files_list($path);
foreach ($list as $entry) {
    $file = $path.'/'.$entry;
    if (is_file($file)) {
        $terms = array_merge($terms,SubLanguageManager::get_all_language_variable_in_file($file,true));
	}
}
// get only the array keys (the language variables defined in language files)
$defined_terms = array_flip(array_keys($terms));
$terms = null;

// now get all terms found in all PHP files of Chamilo (this takes some time and memory)
$undefined_terms = array();
$l = strlen(api_get_path(SYS_PATH));
$files = get_all_php_files(api_get_path(SYS_PATH));
foreach ($files as $file) {
    //echo 'Analyzing '.$file."<br />";
    $shortfile = substr($file,$l);
	$lines = file($file);
    foreach ($lines as $line) {
    	$myterms = array();
        $res = preg_match_all('/get_lang\(\'(\\w*)\'\)/',$line,$myterms);
        if ($res > 0) {
            foreach($myterms[1] as $term) {
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
if (count($undefined_terms)<1) { die("No missing terms<br />\n"); } else { echo "The following terms were nowhere to be found: <br />\n<table>"; }
foreach ($undefined_terms as $term => $file) {
	echo "<tr><td>$term</td><td>in $file</td></tr>\n";
}
echo "</table>\n";


function get_all_php_files($base_path) {
    $list = scandir($base_path);
    $files = array();
    foreach ($list as $item) {
    	if (substr($item,0,1)=='.') {continue;}
        $special_dirs = array(api_get_path(SYS_TEST_PATH),api_get_path(SYS_COURSE_PATH),api_get_path(SYS_LANG_PATH),api_get_path(SYS_ARCHIVE_PATH));
        if (in_array($base_path.$item.'/',$special_dirs)) {continue;}
        if (is_dir($base_path.$item)) {
        	$files = array_merge($files,get_all_php_files($base_path.$item.'/'));
        } else {
            //only analyse php files
        	if (substr($item,-4) == '.php') {
                $files[] = $base_path.$item;
        	}
        } 
    }
    $list = null;
    return $files;
}