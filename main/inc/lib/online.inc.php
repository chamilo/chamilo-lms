<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Istvan Mandak
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
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
 * Enter description here...
 *
 * @param unknown_type $uid
 * @param unknown_type $statistics_database
 * 
 * @todo the second parameter is of no use. 
 */
function LoginCheck($uid,$statistics_database)
{
	global $_course;
	
	$online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	if ($uid!="")
	{
		LoginDelete($uid,$statistics_database);
		$login_ip = $_SERVER['REMOTE_ADDR'];
		$reallyNow = time();
		$login_date = date("Y-m-d H:i:s",$reallyNow);	
		// if the $_course array exists this means we are in a course and we have to store this in the who's online table also
		// to have the x users in this course feature working
		if ($_course)
		{
			$query = "INSERT INTO ".$online_table ." (login_id,login_user_id,login_date,login_ip, course) VALUES ($uid,$uid,'$login_date','$login_ip', '".$_course['id']."')";								
		}
		else
		{
			$query = "INSERT INTO ".$online_table ." (login_id,login_user_id,login_date,login_ip) VALUES ($uid,$uid,'$login_date','$login_ip')";								
		}

		@api_sql_query($query,__FILE__,__LINE__);
	}
}

/**
 * Enter description here...
 *
 * @param unknown_type $uid
 * 
 * @todo the name is not very clear. I would expect that it deletes a login from the tracking info or even it deletes a user.
 */
function LoginDelete($user_id)
{
	$online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$query = "DELETE FROM ".$online_table ." WHERE login_user_id = '".mysql_real_escape_string($user_id)."'";
	@api_sql_query($query,__FILE__,__LINE__);
}

/**
* @todo remove parameter $statistics_database which is no longer necessary
*/
function WhoIsOnline($uid,$statistics_database,$valid)
{				
	$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$query = "SELECT login_user_id,login_date FROM ".$track_online_table ." WHERE DATE_ADD(login_date,INTERVAL $valid MINUTE) >= NOW()  ";	
	$result = @api_sql_query($query,__FILE__,__LINE__);							
	if (count($result)>0)
	{
		$rtime = time();
		$rdate = date("Y-m-d H:i:s",$rtime);
		$validtime = mktime(date("H"),date("i")-$valid,date("s"),date("m"),date("d"),date("Y"));
		$rarray = array();
		
		while(list($login_user_id,$login_date)= mysql_fetch_row($result))
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
			//echo $dbtime.":".$rtime.">".$validtime."<BR>";
			//echo "$login_user_id.":".$login_date.";";
		}					
		return $rarray;
	}
	else 
	{
		return false;
	}
}

function GetFullUserName($uid)
{
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$safe_uid = Database::escape_string($uid);
	$query = "SELECT firstname,lastname FROM ".$user_table." WHERE user_id='$safe_uid'";
	$result = @api_sql_query($query,__FILE__,__LINE__);
	if (count($result)>0)
	{
		$str = "";
		while(list($firstname,$lastname)= mysql_fetch_array($result))
		{
			$str = $lastname."&nbsp;".$firstname;
			return $str;
		}
		
	}
}
		
function GetURL($path)
{
	$str = "";	
	$url = explode('/',$path);			
	for($i=0;$i < sizeof($url)-2; $i++)
	{ 
		if($i==sizeof($url)-3)
			{$str = $str.$url[$i]; }
		else
		{
			$str = $str.$url[$i]."/"; 
		}
	}
	return $str;
}		

// picture? 
function IsValidUser($uid)
{
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	
	$query = "SELECT `picture_uri`  FROM ".$user_table." WHERE `user_id`='$uid'";	
	$result = @api_sql_query($query,__FILE__,__LINE__);
	
	if (count($result)>0)
	{
		while(list($picture_uri)= mysql_fetch_array($result))
		{
			if (count($picture_uri)>0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	else
	{
		return false;
	}
}
	
function ClearURL($path)
{
	$url = explode('?id=',$path);
		return $url[0];
}

function chatcall() {

	global $_user, $_cid;
	
	if (!$_user['user_id'])
	{
		return (false);
	}
	$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
	$sql="select chatcall_user_id, chatcall_date from $track_user_table where ( user_id = '".$_user['user_id']."' )";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=mysql_fetch_array($result);

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
*/
function who_is_online_in_this_course($uid, $valid, $coursecode)
{				
	$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$coursecode = Database::escape_string($coursecode);
	$query = "SELECT login_user_id,login_date FROM ".$track_online_table ." WHERE course='".$coursecode."' AND DATE_ADD(login_date,INTERVAL $valid MINUTE) >= NOW() ";	
	$result = api_sql_query($query,__FILE__,__LINE__);							
	if (count($result)>0)
	{
		$rtime = time();
		$rdate = date("Y-m-d H:i:s",$rtime);
		$validtime = mktime(date("H"),date("i")-$valid,date("s"),date("m"),date("d"),date("Y"));
		$rarray = array();
		
		while(list($login_user_id,$login_date)= mysql_fetch_row($result))
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
			//echo $dbtime.":".$rtime.">".$validtime."<BR>";
			//echo "$login_user_id.":".$login_date.";";
		}					
		return $rarray;
	}
	else 
	{
		return false;
	}
}

?>
