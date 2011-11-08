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
$path = api_get_path(SYS_CODE_PATH).'img/';
ini_set('memory_limit','128M');
ini_set('max_execution_time','240');
/**
 * Main code
 */
$terms = array();
$found_img = get_img_files($path);
// now get all terms found in all PHP files of Chamilo (this takes some time and memory)
$unexisting_img = array();
$l = strlen(api_get_path(SYS_PATH));
$files = get_all_php_files(api_get_path(SYS_PATH));
$counter = 0;
foreach ($files as $file) {
  $shortfile = substr($file,$l);
  $lines = file($file);
  foreach ($lines as $line) {
    $res3 = preg_match_all('/([\w\d-_]+\.png)/',$line,$myterms3);
    $res4 = preg_match_all('/([\w\d-_]+\.jpg)/',$line,$myterms4);
    $res5 = preg_match_all('/([\w\d-_]+\.jpeg)/',$line,$myterms5);
    $res6 = preg_match_all('/([\w\d-_]+\.gif)/',$line,$myterms6);
    $myterms = array_merge($myterms3,$myterms4,$myterms5,$myterms6);
    if (count($myterms)>0) {
      foreach ($myterms as $mytermsentry) {
        if (count($mytermsentry)==0) { continue; }
        foreach ($mytermsentry as $term) {
          if (!isset($found_img[$term])) {
            $unexisting_img[$term] = $shortfile;
          } else {
            $used_icons[$term][] = $shortfile;
          }
        }
      }
    }
  }
  flush();
  $counter++;
}
echo '<table>';
/*if (count($unexisting_img)<1) { die("No missing image<br />\n"); } else { echo "The following images were nowhere to be found: <br />\n<table>"; }
foreach ($unexisting_img as $term => $file) {
	echo "<tr><td>$term</td><td>in $file</td></tr>\n";
}*/
echo '<tr><td colspan="2">Existing images('.count($found_img).'), used('.count($used_icons).') and unused</td></tr>'."\n";
echo '<tr><td>Image file</td><td>Img path</td><td>Used in...</td></tr>'."\n";
$r = ksort($found_img);
foreach ($found_img as $term => $path) {
  if (isset($used_icons[$term])) {
    echo '<tr>';
    echo '<td bgcolor="#55ff55">'.$term.'</td>';
    echo '<td bgcolor="#55ff55">'.($path=='/'?'/':$path.'/').$term.'</td>';
    $st = '';
    foreach ($used_icons[$term] as $entry) {
      $st .= $entry."\n";
    }
    echo '<td bgcolor="#55ff55"><pre>'.$st.'</pre></td>';
    echo '</tr>'."\n";
  } else {
    echo '<tr>';
    echo '<td bgcolor="#ff5555">'.$term.'</td>';
    echo '<td bgcolor="#ff5555">'.($path=='/'?'/':$path.'/').$term.'</td>';
    echo '<td bgcolor="#ff5555">-</td>';
    echo '</tr>'."\n";
  }
}
echo "</table>\n";
echo "Analysed files:<br />\n";
print_r($files);


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
                $ext = substr($item,-4);
        	if (in_array($ext,array('.php','html','.htm','.css'))) {
                  $files[] = $base_path.$item;
        	}
        } 
    }
    $list = null;
    return $files;
}
function get_img_files($path) {
  $files = array();
  //We know there are max 3 levels
  $list = scandir($path);
  foreach ($list as $entry) {
    if (substr($entry,0,1)=='.') { continue; }
    if (is_dir($path.$entry)) {
      $sublist = scandir($path.$entry);
      foreach ($sublist as $subentry) {
        if (substr($subentry,0,1)=='.') { continue; }
        if (is_dir($path.$entry.'/'.$subentry)) {
          $subsublist = scandir($path.$entry.'/'.$subentry);
          foreach ($subsublist as $subsubentry) {
            if (substr($subsubentry,0,1)=='.') { continue; }
            if (is_file($path.$entry.'/'.$subentry.'/'.$subsubentry)) {
              $files[$subsubentry] = '/'.$entry.'/'.$subentry;
            }
          }
        } elseif (is_file($path.$entry.'/'.$subentry)) {
          $files[$subentry] = '/'.$entry;
        }
      }
    } elseif (is_file($path.$entry)) {
      $files[$entry] = '/';
    }
  }
  return $files;
}
