<?php
/* For license terms, see /license.txt */
/**
 * This script allows you to update all the paths in the
 * courses/[CODE]/index.php files when you change your Chamilo installation
 * or create a copy somewhere.
 * How to use:
 * - Copy into your courses directory
 * - Update paths
 * - Run from the command line (php5 fix_course_index.php)
 * - Check the results in one index.php file
 * - Delete this file
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}
$dir = __DIR__;
$list = scandir($dir);
// Set the folders from/to (only the part that needs to be replaced)
$originalPath = 'original.path.com';
$destinationPath = 'destination.path.com';
foreach ($list as $entry) {
    if (substr($entry, 0, 1) == '.') {
        continue;
    }
    if (!is_dir($dir.'/'.$entry)) {
        continue;
    }
    if (!is_file($dir.'/'.$entry.'/index.php')) {
        continue;
    }
    $file = file_get_contents($dir.'/'.$entry.'/index.php');
    $file = preg_replace('/'.$originalPath.'/', $destinationPath, $file);
    file_put_contents($dir.'/'.$entry.'/index.php', $file);
    //die($entry);
}
