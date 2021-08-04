<?php
/* For licensing terms, see /license.txt */

/**
 * Goes through all HTML files of the courses directory and replaces
 * the first string by the second string.
 * This is useful when a portal was installed under one URL and then
 * changed URL (or port), to ensure documents are not pointing to the
 * previous URL.
 * This script is designed to be run from the browser, so maybe you
 * need to move it to an executable folder and change the first require.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
 exit;
require __DIR__.'/../../main/inc/global.inc.php';
api_protect_admin_script();

// Search string
$search = 'be:8181';
$replace = 'be';

$dir = api_get_path(SYS_COURSE_PATH);
$courses = scandir($dir);
$i = 0;
foreach ($courses as $courseDir) {
    if (substr($courseDir, 0, 1) === '.') {
        continue;
    }
    exec('find '.$dir.$courseDir.'/document/ -type f -name "*.html" -exec sed -i '."'s/hn:8181/hn/g' {} +");
    //print('find '.$dir.$courseDir.'/document/ -type f -name "*.html" -exec sed -i '."'s/hn:8181/hn/g' {} +<br />");
    $i++;
    //if ($i == 2) {
    //    exit;
    //}
    echo "Replaced all $search in ".$dir.$courseDir."<br />";
}
echo "Done";
