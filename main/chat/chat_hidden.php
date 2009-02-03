<?php // $Id: chat_hidden.php,v 1.8 2005/05/01 11:49:16 darkden81 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Hidden frame that refreshes the visible frames when a modification occurs
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

define('FRAME','hidden');

$language_file = array ('chat');

require('../inc/global.inc.php');
include_once '../inc/lib/course.lib.php';
require_once 'chat_functions.lib.php';
//$tbl_user=$mainDbName."`.`user";
//$tbl_chat_connected=$_course['dbNameGlu'].'chat_connected';
$tbl_user 		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_chat_connected 	= Database::get_course_chat_connected_table();

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($query,__FILE__,__LINE__);

list($pseudoUser)=Database::fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

/*if(!$isAllowed)
{
	exit();
}*/

$dateNow=date('Y-m-d');

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$chatPath=$documentPath.'chat_files/';

$chat_size_old=intval($_POST['chat_size_old']);
$chat_size_new=filesize($chatPath.'messages-'.$dateNow.'.log.html');

$sql="SELECT user_id FROM $tbl_chat_connected WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($sql);

//The user_id exists so we must do an UPDATE and not a INSERT
$current_time=date('Y-m-d H:i:s');
if (Database::num_rows($result)==0) {
	$query="INSERT INTO $tbl_chat_connected(user_id,last_connection) VALUES('".$_user['user_id']."','".$current_time."')";
} else {
	$query="UPDATE $tbl_chat_connected set last_connection='".$current_time."' WHERE user_id='".$_user['user_id']."'";
}

api_sql_query($query,__FILE__,__LINE__);

$query="SELECT COUNT(user_id) FROM $tbl_chat_connected WHERE last_connection>'".date('Y-m-d H:i:s',time()-60*5)."'";
$result=api_sql_query($query,__FILE__,__LINE__);

$connected_old=intval($_POST['connected_old']);
list($connected_new) = Database::fetch_row($result);
/*disconnected user of chat*/
disconnect_user_of_chat ();
include("header_frame.inc.php");

//navigation menu
if(api_get_setting('show_navigation_menu') != 'false')
{
   $course_id = api_get_course_id();
   if ( !empty($course_id) && ($course_id != -1) )
   {
   		if( api_get_setting('show_navigation_menu') != 'icons')
		{
	    	echo '</div> <!-- end #center -->';
    		echo '</div> <!-- end #centerwrap -->';
		}
      	require_once(api_get_path(INCLUDE_PATH).'tool_navigation_menu.inc.php');
      	show_navigation_menu();
   }
}
?>

<form name="formHidden" method="post" action="<?php echo api_get_self().'?cidReq='.$_GET['cidReq']; ?>">
<input type="hidden" name="chat_size_old" value="<?php echo $chat_size_new; ?>">
<input type="hidden" name="connected_old" value="<?php echo $connected_new; ?>">
</form>

<?php

if ($_SESSION["origin"] == 'whoisonline') {  //check if our target has denied our request or not
	$talk_to=$_SESSION["target"];
	$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
	$sql="select chatcall_text from $track_user_table where ( user_id = $talk_to )";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row=mysql_fetch_array($result);
	if ($row['chatcall_text'] == 'DENIED') {
		echo "<script language=javascript> alert('".get_lang('ChatDenied')."'); </script>";	
		$sql="update $track_user_table set chatcall_user_id = '', chatcall_date = '', chatcall_text='' where (user_id = $talk_to)";
		$result=api_sql_query($sql,__FILE__,__LINE__);
	}
}

include('footer_frame.inc.php');
?>
