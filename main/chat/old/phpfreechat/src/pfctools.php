<?php
/**
 * phpfreechattools.class.php
 *
 * Copyright © 2006 Stephane Gully <stephane.gully@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details. 
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */

/**
 * this file contains a toolbox with misc. usefull functions
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */

// To be sure the directory separator is defined
// I don't know if this constant can be undefined or not so maybe this code is not necessary
if (!defined("DIRECTORY_SEPARATOR"))
  define("DIRECTORY_SEPARATOR", "/");


/**
 * Returns the absolute script filename
 * takes care of php cgi configuration which do not support SCRIPT_FILENAME variable.
 */
function getScriptFilename()
{
  $sf = isset($_SERVER["PATH_TRANSLATED"]) ? $_SERVER["PATH_TRANSLATED"] : ""; // check for a cgi configurations
  if ( $sf == "" ||
       !file_exists($sf))
    $sf = isset($_SERVER["SCRIPT_FILENAME"]) ? $_SERVER["SCRIPT_FILENAME"] : ""; // for 'normal' configurations
  if ( $sf == "" ||
       !file_exists($sf))
  {
    echo "<pre>";
    echo "<span style='color:red'>Error: GetScriptFilename function returns a wrong path. Please contact the pfc team (contact@phpfreechat.net) and copy/paste this array to help debugging.</span>\n";
    print_r($_SERVER);
    echo "</pre>";
    exit;
  }
  return $sf;
}

function relativePath($p1, $p2)
{
  if (is_file($p1)) $p1 = dirname($p1);
  if (is_file($p2)) $p2 = dirname($p2);
  // using realpath function is necessary to resolve symbolic links
  $p1 = realpath(cleanPath($p1));
  $p2 = realpath(cleanPath($p2));
  $res = "";
  //echo $p1."<br>";
  //echo $p2."<br>";
  while( $p1 != "" &&
         $p1 != "/" && // for unix root dir
         !preg_match("/[a-z]\:\\\/i",$p1) && // for windows rootdir
         strpos($p2, $p1) !== 0)
  {
    $res .= "../";
    $p1 = dirname($p1);
  }
  if (isset($_SERVER["WINDIR"]) || isset($_SERVER["windir"])) {
      $p2 = str_replace("\\","/",substr($p2, strlen($p1)+1, strlen($p2)-strlen($p1)));
  } else {
      if ($p1 === "/" || $p1 === "") {
          $p2 = substr($p2, strlen($p1));
      } else {
          $p2 = substr($p2, strlen($p1)+1);
      }
  }
  $res .= $p2;
  // remove the last "/"
  if (preg_match("/.*\/$/", $res)) $res = preg_replace("/(.*)\//","$1",$res);
  // if rootpath is empty replace it by "." to avoide url starting with "/"
  if ($res == "") $res = ".";
  return $res;
}  

function cleanPath($path)
{
  $result = array();
  $pathA = explode(DIRECTORY_SEPARATOR, $path);
  if (!$pathA[0])
    $result[] = '';
  foreach ($pathA AS $key => $dir) {
    if ($dir == '..') {
      if (end($result) == '..') {
        $result[] = '..';
      } elseif (!array_pop($result)) {
        $result[] = '..';
      }
    } elseif ($dir && $dir != '.') {
      $result[] = $dir;
    }
  }
  if (!end($pathA))
    $result[] = '';
  return implode('/', $result);
}


function mkdir_r($path, $modedir = 0775)
{
  // This function creates the specified directory using mkdir().  Note
  // that the recursive feature on mkdir() is broken with PHP 5.0.4 for
  // Windows, so I have to do the recursion myself.
  if (!file_exists($path))
  {
    // The directory doesn't exist.  Recurse, passing in the parent
    // directory so that it gets created.
    mkdir_r(dirname($path), $modedir);
    mkdir($path, $modedir);
  }
}

function rm_r($dir)
{
  if(!$dh = @opendir($dir)) return;
  while (($obj = readdir($dh)))
  {
    if($obj=='.' || $obj=='..') continue;
    if (!@unlink($dir.'/'.$obj)) rm_r($dir.'/'.$obj);
  }
  @rmdir($dir);
}

/**
 * Copy a file, or recursively copy a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @link        http://aidanlister.com/repos/v/function.copyr.php
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
function copy_r($source, $dest, $modedir = 0775, $modefile = 0664)
{ 
  // Simple copy for a file
  if (is_file($source)) {
    $ret = copy($source, $dest);
    chmod($dest, $modefile);
    return $ret;
  }

  // Make destination directory
  if (!is_dir($dest)) {
    mkdir($dest, $modedir);
  }

  // Take the directories entries
  $dir = dir($source);
  $entries = array();
  while (false !== $entry = $dir->read())
  {
    $entries[] = $entry;
  }
  
  // Loop through the folder
  foreach ($entries as $e)
  {
    // Skip pointers and subversion directories
    if ($e == '.' || $e == '..' || $e == '.svn') continue;
    // Deep copy directories
    if ($dest !== $source . DIRECTORY_SEPARATOR . $e)
      copy_r($source . DIRECTORY_SEPARATOR . $e, $dest . DIRECTORY_SEPARATOR . $e, $modedir, $modefile);
  }
  
  // Clean up
  $dir->close();
  return true;
}

/**
 * Check the functions really exists on this server
 */
function check_functions_exist( $f_list )
{
  $errors = array();
  foreach( $f_list as $func => $err )
  {
    if (!function_exists( $func ))
      $errors[] = _pfc("%s doesn't exist: %s", $func, $err);
  }
  return $errors;
}


function test_writable_dir($dir, $name = "")
{
  $errors = array();
  if ($dir == "")
    $errors[] = _pfc("%s directory must be specified", ($name!="" ? $name : $dir));
  
  if (is_file($dir))
    $this->errors[] = _pfc("%s must be a directory",$dir);
  if (!is_dir($dir))
    mkdir_r($dir);
  if (!is_dir($dir))
    $errors[] = _pfc("%s can't be created",$dir);
  if (!is_writeable($dir))
    $errors[] = _pfc("%s is not writeable",$dir);
  if (!is_readable($dir))
    $errors[] = _pfc("%s is not readable",$dir);

  return $errors;
}

function install_file($src_file, $dst_file)
{
  $errors = array();
  
  $src_dir = dirname($src_file);
  $dst_dir = dirname($dst_file);
  
  if (!is_file($src_file))
    $errors[] = _pfc("%s is not a file", $src_file);
  if (!is_readable($src_file))
    $errors[] = _pfc("%s is not readable", $src_file);
  if (!is_dir($src_dir))
    $errors[] = _pfc("%s is not a directory", $src_dir);
  if (!is_dir($dst_dir))
    mkdir_r($dst_dir);

  copy( $src_file, $dst_file );

  return $errors;
}

function install_dir($src_dir, $dst_dir)
{
  $errors = array();
  
  if (!is_dir($src_dir))
    $errors[] = _pfc("%s is not a directory", $src_dir);
  if (!is_readable($src_dir))
    $errors[] = _pfc("%s is not readable", $src_dir);

  copy_r( $src_dir, $dst_dir );

  return $errors;
}



/**
 * file_get_contents
 * define an alternative file_get_contents when this function doesn't exists on the used php version (<4.3.0)
 */
if (!function_exists('file_get_contents'))
{
  function file_get_contents($filename, $incpath = false, $resource_context = null)
  {
    if (false === $fh = fopen($filename, 'rb', $incpath))
    {
      trigger_error('file_get_contents() failed to open stream: No such file or directory', E_USER_WARNING);
      return false;
    }
    clearstatcache();
    if ($fsize = filesize($filename))
    {
      $data = fread($fh, $fsize);
    }
    else
    {
      while (!feof($fh)) {
        $data .= fread($fh, 8192);
      }
    }
    fclose($fh);
    return $data;
  }
}

/**
 * Replace file_put_contents()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.file_put_contents
 * @author      Aidan Lister <aidan@php.net>
 * @internal    resource_context is not supported
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 */
if (!defined('FILE_USE_INCLUDE_PATH')) {
  define('FILE_USE_INCLUDE_PATH', 1);
}

if (!defined('LOCK_EX')) {
  define('LOCK_EX', 2);
}

if (!defined('FILE_APPEND')) {
  define('FILE_APPEND', 8);
}
if (!function_exists('file_put_contents')) {
  function file_put_contents($filename, $content, $flags = null, $resource_context = null)
    {
      // If $content is an array, convert it to a string
      if (is_array($content)) {
        $content = implode('', $content);
      }

      // If we don't have a string, throw an error
      if (!is_scalar($content)) {
        user_error('file_put_contents() The 2nd parameter should be either a string or an array',
                   E_USER_WARNING);
        return false;
      }

      // Get the length of data to write
      $length = strlen($content);

      // Check what mode we are using
      $mode = ($flags & FILE_APPEND) ?
        'a' :
        'wb';

      // Check if we're using the include path
      $use_inc_path = ($flags & FILE_USE_INCLUDE_PATH) ?
        true :
        false;

      // Open the file for writing
      if (($fh = @fopen($filename, $mode, $use_inc_path)) === false) {
        user_error('file_put_contents() failed to open stream: Permission denied',
                   E_USER_WARNING);
        return false;
      }

      // Attempt to get an exclusive lock
      $use_lock = ($flags & LOCK_EX) ? true : false ;
      if ($use_lock === true) {
        if (!flock($fh, LOCK_EX)) {
          return false;
        }
      }

      // Write to the file
      $bytes = 0;
      if (($bytes = @fwrite($fh, $content)) === false) {
        $errormsg = sprintf('file_put_contents() Failed to write %d bytes to %s',
                            $length,
                            $filename);
        user_error($errormsg, E_USER_WARNING);
        return false;
      }

      // Close the handle
      @fclose($fh);

      // Check all the data was written
      if ($bytes != $length) {
        $errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',
                            $bytes,
                            $length);
        user_error($errormsg, E_USER_WARNING);
        return false;
      }

      // Return length
      return $bytes;
    }
}


/**
 * iconv
 * define an alternative iconv when this function doesn't exists on the php modules
 */
if (!function_exists('iconv'))
{
  if(function_exists('libiconv'))
  {
    // use libiconv if it exists
    function iconv($input_encoding, $output_encoding, $string)
    {
      return libiconv($input_encoding, $output_encoding, $string);
    }
  }
  else
  {
    // fallback if nothing has been found
    function iconv($input_encoding, $output_encoding, $string)
    {
      return $string;
    }
  }
}    

?>
