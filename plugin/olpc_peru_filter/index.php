<?php
/* For licensing terms, see /license.txt */
/**
 * This script shows a list of options that are taken from a Squid configuration
 * directory and lets the teacher choose which filter options to enable for his
 * course.
 */
/**
 * Configuration settings. Update if your Squid files are somewhere else
 */
define('BLACKLISTS_ENABLED_FILE','/var/sqg/blacklists');
define('BLACKLISTS_DIR','/var/squidGuard/blacklists');
/**
 * Reading list
 */
$list = scandir(BLACKLISTS_DIR);
$categories = array();
foreach ($list as $file) {
    if (substr($file,0,1) == '.' or $file == 'custom_blacklist' or is_dir(BLACKLIST_DIR.'/'.$file)) {
        continue;
    }
    $categories[] = $file; 
}
sort($categories);
/**
 * Generate a checkboxes list with the names of the categories found in the
 * directory. Already check if the category belongs to a $blacklist
 */
$blacklist = file(BLACKLISTS_ENABLED_FILE);
foreach ($categories as $category) {
    foreach ($blacklist as $blacklisted) {
        $checked = '';
        if ($category == trim($blacklisted)) {
            $checked = ' checked="checked"';
        }
        echo '<input type="checkbox" name="blacklists[]" value="'.$category.'" '.$checked.'>'.$category.'</input><br />';
    }
}
