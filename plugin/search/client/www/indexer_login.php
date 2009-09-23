<?php
/**
 * This file has to be placed at the root level of your Dokeos portal and has
 * to be referenced inside the indexer.conf file on your indexing server, as the
 * first URL to index on your portal.
 */
//the ip_address_of_search_server is the IP address from which your search
// (or indexing) server will connect to your portal to index it
$ip_address_of_search_server = '192.168.1.1';
//the domain_name_of_search_server is the domain name from which your search
// (or indexing) server will connect to your portal to index it
$domain_name_of_search_server = 'your.domain.com';
// indexing_user_id is the database ID of the user you created to be used by
// the indexing server to crawl your portal
$indexing_user_id = 'xxx';

if($_SERVER['REMOTE_ADDR']==$ip_address_of_search_server
	or $_SERVER['REMOTE_HOST'] == $domain_name_of_search_server){

  //make sure we don't display errors if the authentication does not work
  ini_set('display_errors','Off');
  require_once('main/inc/global.inc.php');

  $id = $indexing_user_id;
  //subscribe user to all courses
  $course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
  $course = Database::get_main_table(TABLE_MAIN_COURSE);
  $sql = "DELETE FROM $course_rel_user WHERE user_id = $id";
  $res = @api_sql_query($sql,__FILE__,__LINE__);
  $sql = "SELECT code FROM $course";
  $res = @api_sql_query($sql,__FILE__,__LINE__);
  if(Database::num_rows($res)>0)
  {
    while ($row = Database::fetch_array($res))
    {
      $sql2 = "INSERT INTO $course_rel_user (course_code,user_id,status)VALUES('".$row['code']."',$id,5)";
      $res2 = @api_sql_query($sql2,__FILE__,__LINE__);
    }
  }
  //now login the user to the platform (put everything needed inside the
  // session) and then redirect the search engine to the courses list
  $_SESSION['_user']['user_id'] = $id;
  define('DOKEOS_HOMEPAGE', true);
  require('main/inc/global.inc.php');
  require('user_portal.php');
}
?>