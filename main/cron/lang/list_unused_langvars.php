<?php /* For licensing terms, see /license.txt */
/**
 * Cron script to list unused, but defined, language variables
 * @package chamilo.cron.lang
 */
/**
 * Includes and declarations
 */
die();
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
echo count($defined_terms)." terms were found in language files<br />";

// now get all terms found in all PHP files of Chamilo (this takes some 
// time and memory)
$used_terms = array();
$l = strlen(api_get_path(SYS_PATH));
$files = get_all_php_files(api_get_path(SYS_PATH));
// Browse files
foreach ($files as $file) {
  //echo 'Analyzing '.$file."<br />";
  $shortfile = substr($file,$l);
  //echo 'Analyzing '.$shortfile."<br />";
  $lines = file($file);
  // Browse lines inside file $file
  foreach ($lines as $line) {
    $myterms = array();
    $res = preg_match_all('/get_lang\(\'(\\w*)\'\)/',$line,$myterms);
    if ($res > 0) {
      foreach($myterms[1] as $term) {
        if (substr($term,0,4)=='lang') { $term = substr($term,4); }
        $used_terms[$term] = $shortfile;
      }
    } else {
      $res = 0;
      $res = preg_match_all('/\{[\'"](\\w*)[\'"]\|get_lang\}/',$line,$myterms);
      if ($res > 0) {
        foreach($myterms[1] as $term) {
          if (substr($term,0,4)=='lang') { $term = substr($term,4); }
          $used_terms[$term] = $shortfile;
        }
      }
    }
  }
  flush();
}

// Compare defined terms VS used terms. Used terms should be smaller than
// defined terms, and this should prove the concept that there are much
// more variables than what we really use
if (count($used_terms)<1) { 
  die("No used terms<br />\n"); 
} else {
  echo "The following terms were defined but never used: <br />\n<table>"; 
}
$i = 1;
foreach ($defined_terms as $term => $file) {
  // remove "lang" prefix just in case
  if (substr($term,0,4)=='lang') { $term = substr($term,4); }
  if (!isset($used_terms[$term])) {
    echo "<tr><td>$i</td><td>$term</td></tr>\n";
    $i++;
  }
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
                $sub = substr($item,-4);
        	if ($sub == '.php' or $sub == '.tpl') {
                    $files[] = $base_path.$item;
        	}
        } 
    }
    $list = null;
    return $files;
}
