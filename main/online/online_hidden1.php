<?php
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
*	@package dokeos.online
==============================================================================
*/

define('FRAME','hidden1');

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$tbl_user=Database::get_main_table(TABLE_MAIN_USER);
$tbl_online_connected=Database::get_course_table(TABLE_ONLINE_CONNECTED);

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=Database::query($query,__FILE__,__LINE__);

list($pseudoUser)=Database::fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed)
{
	exit();
}

$dateNow=date('Y-m-d');

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$onlinePath=$documentPath.'online_files/';

$chat_size_old=intval($_POST['chat_size_old']);
$chat_size_new=filesize($onlinePath.'messages-'.$dateNow.'.log');

$query="REPLACE INTO $tbl_online_connected (user_id,last_connection) VALUES('".$_user['user_id']."',NOW())";
Database::query($query,__FILE__,__LINE__);

$query="SELECT COUNT(user_id) FROM $tbl_online_connected WHERE last_connection>'".date('Y-m-d H:i:s',time()-60*5)."'";
$result=Database::query($query,__FILE__,__LINE__);

$connected_old=intval($_POST['connected_old']);
list($connected_new)=Database::fetch_row($result);

$streaming_old=$_POST['streaming_old'];
$streaming_new=md5(print_r(@file($onlinePath.'streaming.txt'),true));

if($isMaster)
{
	$document=trim(stripslashes($_POST['document']));

	if(strstr($document,'online_htmlarea.php'))
	{
		$document.='?size='.filesize($onlinePath.'htmlarea.html');
	}

	$fp=fopen($onlinePath.'document_selected.txt','w');

	if(empty($document))
	{
		fputs($fp,api_get_path(WEB_CODE_PATH).'online/online_working_area.php');
	}
	else
	{
		fputs($fp,$document);
	}

	fclose($fp);
}
else
{
	if(!list($document)=file($onlinePath.'document_selected.txt'))
	{
		$document=api_get_path(WEB_CODE_PATH).'online/online_working_area.php';
	}
}

include('header_frame.inc.php');
?>

<form name="formHidden" method="post" action="<?php echo api_get_self(); ?>">
<input type="hidden" name="document" value="<?php echo htmlentities($document); ?>">
<input type="hidden" name="chat_size_old" value="<?php echo $chat_size_new; ?>">
<input type="hidden" name="connected_old" value="<?php echo $connected_new; ?>">
<input type="hidden" name="streaming_old" value="<?php echo $streaming_new; ?>">
</form>

<?php
include('footer_frame.inc.php');
?>
