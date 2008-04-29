<?php // $Id: mkdirr.php 15180 2008-04-29 22:40:16Z yannoo $
/**
 * Create a directory structure recursively
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.0
 * @param       string   $pathname    The directory structure to create
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
 
function mkdirr($pathname, $mode = null)
{
    // Check if directory already exists
    if (is_dir($pathname) || empty($pathname)) {
        return true;
    }
 
    // Ensure a file does not already exist with the same name
    if (is_file($pathname)) {
        trigger_error('mkdirr() File exists', E_USER_WARNING);
        return false;
    }
 
    // Crawl up the directory tree
    $next_pathname = substr($pathname, 0, strrpos($pathname, DIRECTORY_SEPARATOR));
    if (mkdirr($next_pathname, $mode)) {
        if (!file_exists($pathname)) {
            $res = @mkdir($pathname, $mode);
            if($res == false)
            {
              error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini'),0);
            } 
            return $res;
        }
    }
 
    return false;
}
 
?>
