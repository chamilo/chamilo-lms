<?php
// $Id:
/*
==============================================================================
	Dokeos - elearning and course management software

    Copyright (c) 2008 Furio Petrossi
	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This is the tracking library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================

         * Calculates the time spent on the course
	 * @param integer $user_id the user id
	 * @param string $course_code the course code
*	Funzione scritta da Mario per testare cose 
*/
$language_file = array ('registration', 'index', 'tracking');
require_once('../inc/global.inc.php');

/**
 * Gets the connections to a course as an array of login and logout time
 */	 
function get_connections_to_course($user_id, $course_code) {
    $course_code = Database::escape_string($course_code);
    
    $tbl_track_course = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $tbl_main=Database::get_main_table(TABLE_MAIN_COURSE);
    $sql_query='SELECT visual_code as course_code FROM '.$tbl_main.' c WHERE code="'.$course_code.'";';
    $result=api_sql_query($sql_query,__FILE__,__LINE__);
    $row_query=Database::fetch_array($result,'ASSOC');
    $course_true=isset($row_query['course_code']) ? $row_query['course_code']: $course_code;

    $sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . ' 
    				WHERE user_id = ' . intval($user_id) . '
    				AND course_code="' . $course_true . '" ORDER BY login_course_date';
    
    $rs = api_sql_query($sql);
    $connections  = array();
    
    while ($a_connections = Database::fetch_array($rs)) {
    
        $s_login_date = $a_connections['login_course_date'];
        $s_logout_date = $a_connections['logout_course_date'];
        
        $i_timestamp_login_date = strtotime($s_login_date);
        $i_timestamp_logout_date = strtotime($s_logout_date);
        
        $connections[] = array('login'=>$i_timestamp_login_date, 'logout'=>$i_timestamp_logout_date);
        
    }
    
    return $connections;
}
	
/**
 * Transforms seconds into a time format
 */
function calculHours($seconds)
{
	
  //How many hours ?
  $hours = floor($seconds / 3600);

  //How many minutes ?
  $min = floor(($seconds - ($hours * 3600)) / 60);
  if ($min < 10)
    $min = '0'.$min;

  //How many seconds
  $sec = $seconds - ($hours * 3600) - ($min * 60);
  if ($sec < 10)
    $sec = '0'.$sec;

  return $hours.get_lang('HourShort').' '.$min.':'.$sec;

}

/* MAIN */
//Print headers
$nameTools= get_lang('AccessDetails');
$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$_GET['course'], "name" => get_lang("Users"));
$interbreadcrumb[] = array ("url" => "myStudents.php?cidReq=".$_GET['course']."&student=".$_GET['student']."&details=true&origin=user_course", "name" => get_lang('DetailsStudentInCourse'));

Display :: display_header($nameTools);

$user_id = (int)$_REQUEST['student'];
$course_code=Database::escape_string($_REQUEST['course']);

$TBL_USERINFO_DEF 		= Database :: get_course_table(TABLE_USER_INFO);
$mainUserInfo = api_get_user_info($user_id, $course_code);
echo '<strong>',get_lang('User'),': ',$mainUserInfo['firstName'],' ',$mainUserInfo['lastName'],'</strong> <br />';

$connections = get_connections_to_course($user_id, $course_code);
echo '<strong>'.get_lang('Course').': ',$course_code,' - ',$_course['name'],'</strong><br />';
echo '<strong>',get_lang('DateAndTimeOfAccess'),' - ',get_lang('Duration'),'</strong><br />';
/* Login time against logout time
foreach ($connections as $key=>$data)
{
    echo ("<tr><td>".date("d-m-Y (H:i:s)",$data['login'])."</td><td>".date("d-m-Y (H:i:s)",$data['logout'])."</td></tr>"); 
}
*/
/*
foreach ($connections as $key=>$data)
{
    echo ("<tr><td>".date("d-m-Y (H:i:s)",$data['login'])."</td><td>".calculHours($data['logout']-$data['login'])."</td></tr>"); 
}
echo ("</table>");
*/
foreach ($connections as $key=>$data)
{ 
    echo '&nbsp;&nbsp;'.date('d-m-Y (H:i:s)',$data['login']).' - '.calculHours($data['logout']-$data['login']).'<br />'."\n"; 
}
Display:: display_footer();
?>