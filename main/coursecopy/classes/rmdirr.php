<?php // $Id: rmdirr.php 15180 2008-04-29 22:40:16Z yannoo $
/**
 * Delete a file, or a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.2
 * @param       string   $dirname    Directory to delete
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
function rmdirr($dirname)
{
    // Sanity check
    if (!file_exists($dirname)) {
        return false;
    }
 
    // Simple delete for a file
    if (is_file($dirname)) {
        $res = @unlink($dirname);
	if($res === false)
    	{
      		error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini'),0);
    	}
 	return $res;
    }
 
    // Loop through the folder
    $dir = dir($dirname);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }
 
        // Recurse
        rmdirr("$dirname/$entry");
    }
 
    // Clean up
    $dir->close();
    $res = @rmdir($dirname);
    if($res === false)
    {
      error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors')!=false?$php_errormsg:'error not recorded because track_errors is off in your php.ini'),0);
    }
    return $res;
}
 
?>
