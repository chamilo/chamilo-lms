<?php
/* For licensing terms, see /license.txt */
/**
 * This script wipes out your Chamilo installation completely: databases,
 * courses directories, configuration files and all other temp directories.
 * It only works when launched from the command line and requires Chamilo to
 * be installed (otherwise it will not find the references as to the paths and
 * databases to delete). It only wipes out stuff and directories it knows are 
 * created by Chamilo though, so don't worry about your own files if you didn't
 * store them in variable Chamilo directories.
 * Requires Chamilo LMS 1.9 or greater
 * @chamilo.tests.scripts
 */
/**
 * Security checks
 */
if (PHP_SAPI != 'cli') {
  echo "For security reasons, this script can only be launched from the command line, sorry.";
  exit;
}
if (!isset($argv[1]) || $argv[1] != '--i-am-sure') {
  echo "  This script will completely erase all Chamilo installations based on this\n",
       "  directory. There will be no way to recover it. If you really are sure you\n",
       "  want to do this, please launch this script again using the\n    --i-am-sure\n",
       "  parameter. You've been warned. Don't come complaining!\n";
  exit;
}
if (!file_exists(dirname(__FILE__).'/../main/inc/global.inc.php')) {
  echo "  This script needs to be run from the tests/ directory inside a Chamilo\n", "  installation. Please make sure main/inc/global.inc.php exists, then run this\n", "  script again.\n";
}
if (!is_file(dirname(__FILE__).'/../main/inc/conf/configuration.php')) {
  echo "  This script will only work on an already installed version of Chamilo. The \n", "main/inc/conf/configuration.php file could not be found, which is understood\n", "as Chamilo not being installed.\n";
}
/**
 * Preparing vars
 */
ini_set('track_errors',1);
$_SERVER['SERVER_NAME'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
$root = dirname(__FILE__).'/../';
require $root.'main/inc/global.inc.php';
$courses = api_get_path(SYS_COURSE_PATH);
$main = api_get_path(SYS_CODE_PATH).'inc/conf/';
$global_db = Database::get_main_database();
$users = api_get_path(SYS_CODE_PATH).'upload/users/';
$arch = api_get_path(SYS_ARCHIVE_PATH);
$webpath = api_get_path(WEB_PATH);
$homepath = api_get_path(SYS_PATH).'home';
// With all this, we will still be missing custom languages and CSS dirs
/**
 * Running the cleanup
 */
echo "Assuming ".api_get_path(SYS_PATH)." as Chamilo directory\n";
foreach (array($courses, $users, $arch, $homepath, $main) as $dir) {
  $list = scandir($dir);
  echo "Cleaning $dir\n";
  foreach ($list as $entry) {
    if (substr($entry,0,1) == '.' or strcmp($entry,'htaccess')===0 or strcmp($entry,'index.html')===0 or substr($entry,-9,9)=='.dist.php') {
      //skip files that are part of the Chamilo installation
    } else {
      if ($dir == $homepath and 
        ((is_dir($homepath.$entry) and $entry == 'default_platform_document') 
          or (!is_dir($homepath.$entry) and substr($entry,-5)=='.html') and strlen($entry)<=17)
         ) {
         //skip
      } else {
        if (is_dir($dir.$entry)) {
          //echo "Removing ".$dir.$entry."\n";
          rmdirr($dir.$entry);
        } else {
          //echo "Removing ".$dir.$entry."\n";
          unlink($dir.$entry);
        }
      }
    }
  }
}
echo "Dropping database ".$global_db."\n";
$sql = "DROP DATABASE $global_db";
$res = Database::query($sql);
if ($res === false) {
  echo "Failed dropping database. Please check manually.\n";
} else {
  echo "All clean! Load $webpath to run install again.\n";
}
