<?php //$id$
/**
 * This script contains calls to the various filling scripts that allow a
 * demo presenter to fill his Dokeos with demo data.
 * This script is locked against execution from the browser, to avoid malicious
 * insertion on production portals.
 * To execute, you need the PHP5 Command Line Interface (CLI) to be installed
 * on your system and t launch this script manually using: php5 fill_all.php
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
 
/**
 * Initialisation section
 */
$incdir = dirname(__FILE__).'/../../main/inc/'; 
require $incdir.'global.inc.php';

/**
 * Code logic
 */
//Avoid execution if not from the command line
if (PHP_SAPI != 'cli') { die('This demo-data filling script can only be run from the command line. Please launch it from the command line using: php5 fill_all.php. To enable it from your browser (very highly dangerous), remove the first line of code from the "logic" section of this file.'); }
$eol = PHP_EOL;
$output = '';
$files = scandir(dirname(__FILE__));
foreach ($files as $file) {
	if (substr($file,0,1) == '.' or substr($file,0,5) != 'fill_') { ; } //skip
    else {    	
    	if ($file == basename(__FILE__)) {
    		//skip, this is the current file
    	} else {
            $output .= $eol.'Reading file: '.$file.$eol;
    		require_once $file;
            $function = basename($file,'.php');
            if (function_exists($function)) {
            	$output .= $eol.'Executing function '.$function.$eol;
                $function();
            } else {
                //function not found
            }
    	}
    }
}
/**
 * Display
 */
echo $output.$eol;
echo "Done all$eol";