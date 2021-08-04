<?php

/* For licensing terms, see /license.txt */

/**
 * Cron script to list unused images
 * @package chamilo.cron
 */
/**
 * Includes and declarations
 */
exit;
if (PHP_SAPI!='cli') {
    die('Run this script through the command line or comment this line in the code');
}
require_once __DIR__.'/../../../main/inc/global.inc.php';
$path = api_get_path(SYS_CODE_PATH).'img/';
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '240');
$unused = array();
global $_configuration;

/**
 * Main code
 */
// get all the available images and their directory
$found_img = get_img_files($path);
// Now, for each image, check if there is at least one reference
chdir($_configuration['root_sys']);
foreach ($found_img as $i => $p) {
    $j = 0;
    $output = @shell_exec('rgrep '.$i.' main/');
    $outputs = explode('\n', $output);
    foreach ($outputs as $line) {
        if (substr($line, 0, 5)=='rgrep') {
            //this means a permission error, ignore
        } else {
            $j++;
        }
    }
    if ($j === 0) {
        $unused[$i] = $p;
    }
}

echo '<table>';
/*
if (count($unexisting_img)<1) { die("No missing image<br />\n"); } else { echo "The following images were nowhere to be found: <br />\n<table>"; }
foreach ($unexisting_img as $term => $file) {
    echo "<tr><td>$term</td><td>in $file</td></tr>\n";
}
*/
echo '<tr><td colspan="2">Existing images('.count($found_img).'), unused('.count($unused).')</td></tr>'."\n";
echo '<tr><td>Image file</td><td>Used x times</td></tr>'."\n";
$r = ksort($found_img);
foreach ($unused as $term => $path) {
    if (isset($unused[$term])) {
        echo '<tr>';
        echo '<td bgcolor="#55ff55">'.$term.'</td>';
        echo '<td bgcolor="#55ff55">'.($path=='/'?'/':$path.'/').$term.'</td>';
        echo '</tr>'."\n";
    } else {
        echo '<tr>';
        echo '<td bgcolor="#ff5555">'.$term.'</td>';
        echo '<td bgcolor="#ff5555">'.($path=='/'?'/':$path.'/').$term.'</td>';
        echo '</tr>'."\n";
    }
}
echo "</table>\n";

/**
 * Get the list of available images
 * @param string $path The path to start the scan from
 * @return array The files list
 */
function get_img_files($path)
{
    $files = array();
    //We know there are max 3 levels, so don't bother going recursive
    $list = scandir($path);
    foreach ($list as $entry) {
        if (substr($entry, 0, 1)=='.') {
            continue;
        }
        if (is_dir($path.$entry)) {
            $sublist = scandir($path.$entry);
            foreach ($sublist as $subentry) {
                if (substr($subentry, 0, 1)=='.') {
                    continue;
                }
                if (is_dir($path.$entry.'/'.$subentry)) {
                    $subsublist = scandir($path.$entry.'/'.$subentry);
                    foreach ($subsublist as $subsubentry) {
                        if (substr($subsubentry, 0, 1)=='.') {
                            continue;
                        }
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
