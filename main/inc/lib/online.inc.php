<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) Istvan Mandak

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Code library for showing Who is online
*
*	@author Istvan Mandak, principal author
*	@author Denes Nagy, principal author
*	@author Bart Mollet
*	@author Roan Embrechts, cleaning and bugfixing
*	@package dokeos.whoisonline
==============================================================================
*/
/**
 * Insert a login reference for the current user into the track_e_online stats table.
 * This table keeps trace of the last login. Nothing else matters (we don't keep traces of anything older)
 * @param int user id
 * @return void
 */
function LoginCheck($uid)
{
	global $_course;
	$uid = (int) $uid;
	$online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	if (!empty($uid))
	{
        $login_ip = '';
        if(!empty($_SERVER['REMOTE_ADDR']))
        {
		  $login_ip = Database::escape_string($_SERVER['REMOTE_ADDR']);
        }
		$reallyNow = time();
		$login_date = date("Y-m-d H:i:s",$reallyNow);
		// if the $_course array exists this means we are in a course and we have to store this in the who's online table also
		// to have the x users in this course feature working
		if (is_array($_course) && count($_course)>0 && !empty($_course['id']))
		{
            $query = "REPLACE INTO ".$online_table ." (login_id,login_user_id,login_date,login_ip, course) VALUES ($uid,$uid,'$login_date','$login_ip', '".$_course['id']."')";
		}
		else
		{
            $query = "REPLACE INTO ".$online_table ." (login_id,login_user_id,login_date,login_ip) VALUES ($uid,$uid,'$login_date','$login_ip')";
		}

		@Database::query($query,__FILE__,__LINE__);
	}
}

/**
 * This function handles the logout and is called whenever there is a $_GET['logout']
 * @return void  Directly redirects the user or leaves him where he is, but doesn't return anything
 * @author Fernando P. García <fernando@develcuy.com>
 */
function online_logout() {
    global $_configuration, $extAuthSource;
    // variable initialisation
    $query_string='';

    if (!empty($_SESSION['user_language_choice'])) {
        $query_string='?language='.$_SESSION['user_language_choice'];
    }

    // Database table definition
    $tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

    // selecting the last login of the user
    $uid = intval($_GET['uid']);
    $sql_last_connection="SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='$uid' ORDER BY login_date DESC LIMIT 0,1";
    $q_last_connection=Database::query($sql_last_connection);
    if (Database::num_rows($q_last_connection)>0) {
        $i_id_last_connection=Database::result($q_last_connection,0,"login_id");
    }

    if (!isset($_SESSION['login_as'])) {
        $current_date=date('Y-m-d H:i:s',time());
        $s_sql_update_logout_date="UPDATE $tbl_track_login SET logout_date='".$current_date."' WHERE login_id='$i_id_last_connection'";
        Database::query($s_sql_update_logout_date);
    }
    LoginDelete($uid, $_configuration['statistics_database']); //from inc/lib/online.inc.php - removes the "online" status

    //the following code enables the use of an external logout function.
    //example: define a $extAuthSource['ldap']['logout']="file.php" in configuration.php
    // then a function called ldap_logout() inside that file
    // (using *authent_name*_logout as the function name) and the following code
    // will find and execute it
    $uinfo = api_get_user_info($uid);
    if (($uinfo['auth_source'] != PLATFORM_AUTH_SOURCE) && is_array($extAuthSource)) {
        if (is_array($extAuthSource[$uinfo['auth_source']])) {
            $subarray = $extAuthSource[$uinfo['auth_source']];
            if (!empty($subarray['logout']) && file_exists($subarray['logout'])) {
                require_once($subarray['logout']);
                $logout_function = $uinfo['auth_source'].'_logout';
                if (function_exists($logout_function)) {
                    $logout_function($uinfo);
                }
            }
        }
    }
    require_once api_get_path(SYS_PATH) . 'main/chat/chat_functions.lib.php';
    exit_of_chat($uid);
    api_session_destroy();
    global $logout_no_redirect;
    if (!$logout_no_redirect) {
        header("Location: index.php$query_string");
        exit;
    }
}

/**
 * Remove all login records from the track_e_online stats table, for the given user ID.
 * @param int User ID
 * @return void
 */
function LoginDelete($user_id)
{
	$online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $user_id = (int) $user_id;
	$query = "DELETE FROM ".$online_table ." WHERE login_user_id = '".Database::escape_string($user_id)."'";
	@Database::query($query,__FILE__,__LINE__);
}

/**
 * Gives a list of people online now (and in the last $valid minutes)
 * @param   int User ID - useless
 * @param   string      Statistics database name - useless
 * @param   int         Number of minutes to account logins for
 * @return  array       For each line, a list of user IDs and login dates, or FALSE on error or empty results
 * @todo remove parameter $statistics_database which is no longer necessary
 */
function WhoIsOnline($uid=0,$statistics_database='',$valid)
{
	$valid = (int) $valid;
	$current_date=date('Y-m-d H:i:s',time());
	$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$query = "SELECT login_user_id,login_date FROM ".$track_online_table ." WHERE DATE_ADD(login_date,INTERVAL $valid MINUTE) >= '".$current_date."'  ";

	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){
			$query = "SELECT login_user_id,login_date FROM ".$track_online_table ." track
			INNER JOIN $tbl_user_rel_access_url user_rel_url
			ON (user_rel_url.user_id = track.login_user_id)
			WHERE access_url_id =  $access_url_id AND DATE_ADD(login_date,INTERVAL $valid MINUTE) >= '".$current_date."'  ";
		}
	}

	$result = @Database::query($query,__FILE__,__LINE__);
	if (count($result)>0)
	{
		$rtime = time();
		$rdate = date("Y-m-d H:i:s",$rtime);
		$validtime = mktime(date("H"),date("i")-$valid,date("s"),date("m"),date("d"),date("Y"));
		$rarray = array();

		while(list($login_user_id,$login_date)= Database::fetch_row($result))
		{
			$barray = array();
			array_push($barray,$login_user_id);
			array_push($barray,$login_date);

			// YYYY-MM-DD HH:MM:SS, db date format
			$hour = substr($login_date,11,2);
			$minute = substr($login_date,14,2);
			$secund = substr($login_date,17,2);
			$month = substr($login_date,5,2);
			$day = substr($login_date,8,2);
			$year = substr($login_date,0,4);
			// db timestamp
			$dbtime = mktime($hour,$minute,$secund,$month,$day,$year);

			if ($dbtime>$validtime)
			{
				array_push($rarray,$barray);
			}
		}
		return $rarray;
	}
	else
	{
		return false;
	}
}

/**
 * Gets the full user name for a given user ID
 * @param   int User ID
 * @return  string  The full username, elements separated by an HTML space
 */
function GetFullUserName($uid)
{
	$uid = (int) $uid;
	$uid = Database::escape_string($uid);
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$query = "SELECT firstname,lastname FROM ".$user_table." WHERE user_id='$uid'";
	$result = @Database::query($query,__FILE__,__LINE__);
	if (count($result)>0)
	{
		$str = '';
		while(list($firstname,$lastname)= Database::fetch_array($result))
		{
			$str = str_replace(' ', '&nbsp;', api_get_person_name($firstname, $lastname));
			return $str;
		}
	}
}

/**
 * Gets a list of chat calls made by others to the current user (info kept in main.user table)
 * @param   none - taken from global space
 * @return  string  An HTML-formatted message
 */
function chatcall() {

	global $_user, $_cid;

	if (!$_user['user_id'])
	{
		return (false);
	}
	$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
	$sql="select chatcall_user_id, chatcall_date from $track_user_table where ( user_id = '".$_user['user_id']."' )";
	$result=Database::query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result);

	$login_date=$row['chatcall_date'];
	$hour = substr($login_date,11,2);
	$minute = substr($login_date,14,2);
	$secund = substr($login_date,17,2);
	$month = substr($login_date,5,2);
	$day = substr($login_date,8,2);
	$year = substr($login_date,0,4);
	$calltime = mktime($hour,$minute,$secund,$month,$day,$year);

	$time = time();
	$time = date("Y-m-d H:i:s", $time);
	$minute_passed=5;  //within this limit, the chat call request is valid
	$limittime = mktime(date("H"),date("i")-$minute_passed,date("s"),date("m"),date("d"),date("Y"));

	if (($row['chatcall_user_id']) and ($calltime>$limittime)) {
		$webpath=api_get_path(WEB_CODE_PATH);
		$message=get_lang('YouWereCalled').' : '.GetFullUserName($row['chatcall_user_id'],'').'<br>'.get_lang('DoYouAccept')
							."<p>"
				."<a href=\"".$webpath."chat/chat.php?cidReq=".$_cid."&origin=whoisonlinejoin\">"
				. get_lang("Yes")
				."</a>"
				."&nbsp;&nbsp;|&nbsp;&nbsp;"
				."<a href=\"".api_get_path('WEB_PATH')."webchatdeny.php\">"
				. get_lang("No")
				."</a>"
				."</p>";

		return($message);
	}
	else
	{
		return(false);
	}

}

/**
* Returns a list (array) of users who are online and in this course.
* @param    int User ID
* @param    int Number of minutes
* @param    string  Course code (could be empty, but then the function returns false)
* @return   array   Each line gives a user id and a login time
*/
function who_is_online_in_this_course($uid, $valid, $coursecode=null)
{
	if(empty($coursecode)) return false;
	$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$coursecode = Database::escape_string($coursecode);
	$valid = Database::escape_string($valid);

	$query = "SELECT login_user_id,login_date FROM ".$track_online_table ." WHERE course='".$coursecode."' AND DATE_ADD(login_date,INTERVAL $valid MINUTE) >= NOW() ";
	$result = Database::query($query,__FILE__,__LINE__);
	if (count($result)>0)
	{
		$rtime = time();
		$rdate = date("Y-m-d H:i:s",$rtime);
		$validtime = mktime(date("H"),date("i")-$valid,date("s"),date("m"),date("d"),date("Y"));
		$rarray = array();

		while(list($login_user_id,$login_date)= Database::fetch_row($result))
		{
			$barray = array();
			array_push($barray,$login_user_id);
			array_push($barray,$login_date);

			// YYYY-MM-DD HH:MM:SS, db date format
			$hour = substr($login_date,11,2);
			$minute = substr($login_date,14,2);
			$secund = substr($login_date,17,2);
			$month = substr($login_date,5,2);
			$day = substr($login_date,8,2);
			$year = substr($login_date,0,4);
			// db timestamp
			$dbtime = mktime($hour,$minute,$secund,$month,$day,$year);
			if ($dbtime >= $validtime)
			{
				array_push($rarray,$barray);
			}
		}
		return $rarray;
	}
	else
	{
		return false;
	}
}