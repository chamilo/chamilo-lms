<?php
#if($_SERVER['REMOTE_HOST']=='ns2667.ovh.net'){
//if($_SERVER['REMOTE_ADDR']=='81.245.59.78'){
  require_once('main/inc/global.inc.php');
  //user mngo:gno on pf_resi platform: 223
  $_SESSION['_user']['user_id'] = 1;
  define('DOKEOS_HOMEPAGE', true);
  require('main/inc/global.inc.php');
  require('user_portal.php');
//}
?>
