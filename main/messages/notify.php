<?php  // $Id: notify.php 20962 2009-05-25 03:15:53Z iflorespaz $
/* For licensing terms, see /dokeos_license.txt */
  require_once '../inc/global.inc.php';
  require_once api_get_path(LIBRARY_PATH).'message.lib.php';
  header("Cache-Control: no-cache, must-revalidate");
  echo MessageManager::get_new_messages();
?>