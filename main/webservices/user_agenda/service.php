<?php //$id: $
/**
 * This script provides the caller service with a 
 * list of events between two dates for a given user.
 * It is set to work with the Dokeos module for Drupal:
 * http://drupal.org/project/dokeos
 * 
 * See license terms in /dokeos_license.txt
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
require_once('../../inc/global.inc.php');
/**
 * Get a list of events between two dates for the given username
 * Function registered as service. Returns strings in UTF-8.
 * @param string Security key (the Dokeos install's API key)
 * @param string Username
 * @param int    Start date, in YYYYMMDD format
 * @param int    End date, in YYYYMMDD format
 * @return array Courses list (code=>[title=>'title',url='http://...',teacher=>'...',language=>''],code=>[...],...)
 */
function events_list($security_key,$username,$datestart=0,$dateend=0) {
	
	global $_configuration;
   	// check if this script is launch by server and if security key is ok
   	if ( $security_key != $_configuration['security_key'] )
   	{
   		return array('error_msg'=>'Security check failed');
   	}
   	
   	
   	// libraries
	require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	$charset = api_get_setting('platform_charset');
	
	$events_list = array();
	
	$user_id = UserManager::get_user_id_from_username($username);
	if ($user_id === false) { return $events_list; } //error in user id recovery
	require_once '../../calendar/myagenda.inc.php';
	$ds = substr($datestart,0,4).'-'.substr($datestart,4,2).'-'.substr($datestart,6,2).' 00:00:00';
	$de = substr($dateend,0,4).'-'.substr($dateend,4,2).'-'.substr($dateend,6,2).' 00:00:00';
	$events_list = get_personal_agenda_items_between_dates($user_id, $ds, $de);
	foreach ( $events_list as $i => $event )
	{
		$events_list[$i]['title'] = mb_convert_encoding($event['title'],'UTF-8',$charset);
		$events_list[$i]['coursetitle'] = mb_convert_encoding($event['coursetitle'],'UTF-8',$charset);
	}
	return $events_list;
}

header('Content-Type: text/xml; charset=utf-8');
echo '<?xml version="1.0"?>';
echo '<eventslist>';

if(empty($_POST['security-key']) or empty($_POST['username']))
{
	echo '<errormsg>Invalid parameters, this script expects a security-key and a username parameters</errormsg>';
}
else
{
	$events_list = events_list($_POST['security-key'],$_POST['username'],$_POST['datestart'],$_POST['dateend']);
	foreach ( $events_list as $event ) {
		echo '<event>';
		echo '<datestart>' , $event['datestart'] , '</datestart>';
		echo '<dateend>' , $event['dateend'] , '</dateend>';
		echo '<title>' , $event['title'] , '</title>';
		echo '<link>' , $event['link'] , '</link>';
		echo '<coursetitle>' , $event['coursetitle'] , '</coursetitle>';
		echo '</event>';
	}
}
echo '</eventslist>';