<?php
/* For licensing terms, see /license.txt */
/**
 * This script prints a list of most used language terms. The registration of
 * frequency for language variables is a very heavy operation. 
 * To enable, add "$_configuration['language_measure_frequency' ] = 1;" at the
 * end of main/inc/conf/configuration.php. Remove when done.
 */
/**
 * Requires
 */
require_once '../../inc/global.inc.php';
require_once 'langstats.class.php';
/**
 * Init
 */
$ls = new langstats();
if ($ls === false) {
  exit($ls->error);
}
$list = $ls->get_popular_terms(1000);
/**
 * Display
 */
if (count($list)==0) { echo 'No terms loaded so far'; }
if (count($list)>0) {
  echo 'Number of records: '.count($list).'<br />';
  echo '<table><tr><th>Count</th><th>Registration order</th><th>Term</th><th>Count</th></tr>';
  $i = 1;
  foreach($list as $elem) {
    echo '<tr><td>',$i,
      '</td><td>',$elem['id'],
      '</td><td>',$elem['term_name'],
      '</td><td>',$elem['term_count'],'</td></tr>';
    $i++;
  }
  echo '</table>';
}
