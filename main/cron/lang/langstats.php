<?php
/* For licensing terms, see /license.txt */
/**
 * This script prints a list of most used language terms. The registration of
 * frequency for language variables is a very heavy operation. 
 * To enable, add "$_configuration['language_measure_frequency' ] = 1;" at the
 * end of main/inc/conf/configuration.php. Remove when done.
 * Add ?output=1 to the URL to generate languag files in /tmp/lang/ with just
 * the number of terms you want.
 */
/**
 * Requires
 */
die();
$language_file = array(
'accessibility', 'gradebook', 'registration', 'admin', 'group', 'reportlib', 
'agenda', 'help', 'reservation', 'announcements', 'hotspot', 'resourcelinker', 
'blog', 'import', 'scormbuilder', 'chat', 'scormdocument', 'coursebackup', 
'index', 'scorm', 'course_description', 'install', 'shibboleth', 
'course_home', 'learnpath', 'slideshow', 'course_info', 'link', 'survey', 
'courses', 'md_document', 'tracking', 'create_course', 'md_link', 
'trad4all', 'document', 'md_mix', 'userInfo', 'dropbox', 'md_scorm', 
'videoconf', 'exercice', 'messages', 'wiki', 'external_module', 'myagenda', 
'work', 'forum', 'notebook', 'glossary', 'notification'
);
require_once '../../inc/global.inc.php';
require_once 'langstats.class.php';
/**
 * Init
 */
$terms_limit = 10000 + 50;
$x_most_popular = 2000;
$output = false;
$ls = new langstats();
if ($ls === false) {
  exit($ls->error);
}
$list = $ls->get_popular_terms($x_most_popular);
if ($_GET['output'] == 1) {
  $output = true;
  $variables_origin = $ls->get_variables_origin();
}
/**
 * Display
 */
if (count($list)==0) { echo 'No terms loaded so far'; }
if (count($list)>0) {
  $i = 1;
  $j = 1;
  $k = 0;
  $files = array();
  $trans = array();
  echo 'Number of records: '.count($list).'<br />';
  echo '<table><tr><th>Index</th><th>Registration order</th><th>Term</th>'.($output==1?'<th>Origin</th>':'').'<th>Count</th></tr>';
  foreach($list as $elem) {
    if ($k > $terms_limit) { break; }
    $fixed_elem = $elem;
    if ($output) {
      if (empty($variables_origin[$elem['term_name']]) && !empty($variables_origin['lang'.$elem['term_name']])) {
        $fixed_elem = array('id' => $elem['id'], 'term_name' => 'lang'.$elem['term_name'], 'term_count' => $elem['term_count']);
      }
      if (empty($variables_origin[$fixed_elem['term_name']])) {
        continue; 
      }
      $files[$variables_origin[$fixed_elem['term_name']]][] = $fixed_elem['term_name'];
      $translation = get_lang($fixed_elem['term_name']);
      $k += str_word_count($translation);
      $trans[$fixed_elem['term_name']] = $translation;
      $j++;
    }
    echo '<tr><td>',$i,
      '</td><td>',$fixed_elem['id'],
      '</td><td>',$fixed_elem['term_name'];
    if ($output) {
      echo '</td><td>'.$variables_origin[$fixed_elem['term_name']];
    }
    echo '</td><td>',$fixed_elem['term_count'],'</td></tr>';
    $i++;
  }
  echo '</table>';
  if ($output) {
    @mkdir('/tmp/lang');
    foreach ($files as $file => $terms) {
      @touch('/tmp/lang/'.$file);
      file_put_contents('/tmp/lang/'.$file,"<?php".PHP_EOL);
      foreach ($terms as $term) {
        file_put_contents('/tmp/lang/'.$file,'$'.$term.' = "'.str_replace('"','\"',$trans[$term]).'";'.PHP_EOL, FILE_APPEND);
      }
    }  
  }
}
