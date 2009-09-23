<?php // $Id: online_streaming.php 20467 2009-05-11 08:38:29Z ivantcholakov $
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
*	Allows to define the format and the URL of the audio or video stream
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','streaming');

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($query,__FILE__,__LINE__);

list($pseudoUser)=mysql_fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed || !$isMaster)
{
	exit();
}

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$onlinePath=$documentPath.'online_files/';

if($_POST['sent'])
{
	$sent=1;

	$stream_url=trim(stripslashes($_POST['stream_url']));
	$stream_type=trim(stripslashes($_POST['stream_type']));

	if(!empty($stream_url) && $stream_url != 'http://')
	{
		$fp=fopen($onlinePath.'streaming.txt','w');

		fputs($fp,$stream_url."\n".$stream_type);

		fclose($fp);
	}

	mysql_close();
	header('Location: online_master.php');
	exit();
}
else
{
	if(!list($stream_url,$stream_type)=@file($onlinePath.'streaming.txt'))
	{
		$stream_url='';
		$stream_type='mp3';
	}
	else
	{
		$stream_url=rtrim($stream_url);
		$stream_type=rtrim($stream_type);
	}
}

include('header_frame.inc.php');
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
  <td width="1%" valign="middle"><a href="online_master.php"><img src="../img/home.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('Back'),ENT_QUOTES,$charset); ?>"></a></td>
  <td width="99%" align="left">&nbsp;<a href="online_master.php"><?php echo get_lang('Back'); ?></a></td>
</tr>
</table>

<br>

<form method="post" action="<?php echo api_get_self(); ?>">
<input type="hidden" name="sent" value="1">
<table border="0" cellpadding="3" cellspacing="0">
<tr>
  <td width="45%"><?php echo get_lang('StreamURL'); ?> :</td>
  <td width="55%"><input type="text" name="stream_url" size="10" maxlength="100" value="<?php if(!empty($stream_url)) echo htmlentities($stream_url); else echo 'http://'; ?>" style="width: 100px;"></td>
</tr>
<tr>
  <td width="45%" valign="middle"><?php echo get_lang('StreamType'); ?> :</td>
  <td width="55%">
	<input type="radio" name="stream_type" value="mp3" <?php if($stream_type == 'mp3') echo 'checked="checked"'; ?> > MP3<br>
	<input type="radio" name="stream_type" value="mp4" <?php if($stream_type == 'mp4') echo 'checked="checked"'; ?> > MP4<br>
	<input type="radio" name="stream_type" value="mov" <?php if($stream_type == 'mov') echo 'checked="checked"'; ?> > MOV<br>
  </td>
</tr>
<tr>
  <td colspan="2" align="center"><input type="submit" value="<?php echo api_htmlentities(get_lang('Modify'),ENT_QUOTES,$charset); ?>"></td>
</tr>
</table>
</form>

<?php
include('footer_frame.inc.php');
?>
