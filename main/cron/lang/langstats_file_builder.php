<?php
/* For licensing terms, see /license.txt */
/**
 * This script generates a directory based on the English language variables
 * but only composed of the 10,000 (can be configured) most frequent words
 * used in Chamilo. This implies first using the langstats.php script, which
 * in turn implies configuring an additional variable in configuration.php
 * (see langstats.php for more info).
 * When running the language_builder, please make sure this parameter is
 * set to 0 in the configuration.php file, otherwise it will take *ages*.
 */
/**
 * Requires
 */
$language_file = array(
'accessibility',
'admin',
'agenda',
'announcements',
'blog',
'chat',
'course_description',
'course_home',
'course_info',
'coursebackup',
'courses',
'create_course',
'document',
'dropbox',
'exercice',
'external_module',
'forum',
'glossary',
'gradebook',
'group',
'help',
'hotspot',
'import',
'index',
'install',
'learnpath',
'link',
'md_document',
'md_link',
'md_mix',
'md_scorm',
'messages',
'myagenda',
'notebook',
'notification',
'pedaSuggest',
'registration',
'reportlib',
'reservation',
'resourcelinker',
'scorm',
'scormbuilder',
'scormdocument',
'shibboleth',
'slideshow',
'survey',
'tracking',
'trad4all',
'userInfo',
'videoconf',
'wiki',
'work',
);
require_once '../../inc/global.inc.php';
require_once 'langstats.class.php'; 
global $_configuration;
$_configuration['language_measure_frequency'] = 0;
$langstats = new langstats();
$orig_lang = 'english';
/**
 * Init
 */
$words_limit = 10000; //change this if you want more words
$terms_limit = 3000; //change this if you think you'll need more terms
$terms = $langstats->get_popular_terms($terms_limit);
$words_counter = 0;
$i = 0;
$terms_in_limit = array();
$lang_dir = api_get_path(SYS_LANG_PATH);
$arch_dir = api_get_path(SYS_ARCHIVE_PATH);
/**
 * Code run
 */
foreach ($terms as $row) {
  if ($words_counter > 10000) { break; }
  $words = str_word_count(get_lang($row['term_name'],null,$orig_lang));
  $words_counter += $words;
  $terms_in_limit[$row['term_name']] = $i;
  //echo "Term <b>".$row['term_name']."</b> is <b>'".get_lang($row['term_name'],null,$orig_lang)."'</b> which means $words words<br /><br />\n";
  //if ($words_counter%1000 >= 0) {
    //echo "Reached $words_counter words at term $i (".$row['term_name']." used ".$row['term_count']." times)...<br />\n";
  //}
  $i++;
}
//echo $words_counter.'<br />';

echo "Reached ".count($terms_in_limit)." terms for the $words_counter most-used words<br /><br />\n";

echo "Scanning English files, trying to find these terms...<br />\n";
if (!is_dir($arch_dir.'/langstats')) {
  mkdir($arch_dir.'/langstats');
  mkdir($arch_dir.'/langstats/'.$orig_lang);
}
$list_files = scandir($lang_dir.'/'.$orig_lang);
$j = 1;
$terms_found = array();
$words_found = 0;
$global_var = array(); //keep the combination of all vars
$terms_in_limit = array_flip($terms_in_limit);
foreach ($list_files as $file) {
  if (substr($file,0,1) == '.') {continue;}
  //echo "'".substr($file,0,-8)."',<br />"; //print in a PHP array format
  $vars = file($lang_dir.'/'.$orig_lang.'/'.$file);
  $local_var = array();
  $file_string = '<?php'."\n";
  foreach ($vars as $line) {
    $var = array();
    $res = preg_match('/^(\$\w*)/',$line,$var);
    if ($res>0) {
      //echo $var[1]."<br />";
      
      if (in_array(substr($var[1],1),$terms_in_limit)) {
        //echo "Var ".$var[1]." was in the limit<br />";
        $local_var[$var[1]] = $line;
        $file_string .= $line;
        $terms_found[] = substr($var[1],1); //e.g. store Tools
        $words_found += str_word_count(get_lang($var[1],null,$orig_lang));
      } elseif (in_array(substr($var[1],5),$terms_in_limit)) {
        //echo "Var ".$var[1]." was in the limit<br />";
        $local_var[$var[1]] = $line;
        $file_string .= $line;
        $terms_found[] = substr($var[1],5); //e.g. store langTools
        $words_found += str_word_count(get_lang(substr($var[1],5),null,$orig_lang));
      } //else do not care
    }
  }
  echo "Writing ".$arch_dir.'/langstats/'.$orig_lang.'/'.$file."<br />\n";
  file_put_contents($arch_dir.'/langstats/'.$orig_lang.'/'.$file,$file_string); 
  $global_var += $local_var;
};
$terms_diff = count($global_var)-count($terms_in_limit);
echo count($global_var)." terms found in English files (summing up to $words_found words). Some terms ($terms_diff in this case) might have appeared in two different files<br />";
/**
 * Display results
 */

echo "Difference between filtered and found in English:<br />";
//print_r($terms_found);
echo "<pre>".print_r(array_diff($terms_in_limit,$terms_found),1)."</pre>";
echo "#";
