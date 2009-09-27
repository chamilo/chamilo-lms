<?php // $Id: online_master.php 20467 2009-05-11 08:38:29Z ivantcholakov $
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
*	Displays the master's picture and/or the video or audio file
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','master');

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$init=intval($_GET['init']);

$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_online_link=Database::get_course_table(TABLE_ONLINE_LINK);

$query="SELECT t1.user_id,username,picture_uri,t2.status FROM $tbl_user t1,$tbl_course_user t2 WHERE t1.user_id=t2.user_id AND course_code='$_cid' AND (t1.user_id='".$_user['user_id']."' OR t2.status='1')";
$result=api_sql_query($query,__FILE__,__LINE__);

while($row=mysql_fetch_array($result))
{
	if($row['user_id'] == $_user['user_id'])
	{
		$pseudoUser=$row['username'];
	}

	if($row['status'] == 1)
	{
		$picture_uri=$row['picture_uri'];
	}
}

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed)
{
	exit();
}

$pictureURL=api_get_path(WEB_CODE_PATH).'upload/users/';
$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$onlinePath=$documentPath.'online_files/';

if(!is_dir($onlinePath))
{
	if(is_file($onlinePath))
	{
		@unlink($onlinePath);
	}

	@mkdir($onlinePath,0777);
	@chmod($onlinePath,0777);
}

if($isMaster && $init)
{
	$fp=fopen($onlinePath.'htmlarea.html','w');

	fputs($fp,get_lang('TextEditorDefault'));

	fclose($fp);

	if(!file_exists($onlinePath.'streaming.txt'))
	{
		$fp=fopen($onlinePath.'streaming.txt','w');

		fputs($fp,"http://www.dokeos.com/pub/01_discovery_high.mov\nmov");

		fclose($fp);
	}
}

if(!$isMaster)
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

	$query="SELECT id,name,url FROM $tbl_online_link ORDER BY name";
	$result=api_sql_query($query,__FILE__,__LINE__);

	$Links=Database::store_result($result);
}

include('header_frame.inc.php');
?>

<?php if($isMaster): ?>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
  <td width="1%" valign="middle"><a href="online_streaming.php"><img src="../img/conf.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('Streaming'),ENT_QUOTES,$charset); ?>"></a></td>
  <td width="49%" align="left" nowrap="nowrap">&nbsp;<a href="online_streaming.php"><?php echo get_lang('Streaming'); ?></a></td>
  <td width="49%" align="right" nowrap="nowrap"><a href="online_htmlarea.php" target="online_working_area"><?php echo get_lang('WhiteBoard'); ?></a>&nbsp;</td>
  <td width="1%" valign="middle"><a href="online_htmlarea.php" target="online_working_area"><img src="../img/works.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('WhiteBoard'),ENT_QUOTES,$charset); ?>"></a></td>
</tr>
</table>

<br>
<?php endif; ?>

<table border="0" cellpadding="5" cellspacing="0" width="100%" <?php if(!$isMaster) echo 'height="100%"'; ?> >
<tr>

<?php if($isMaster || empty($stream_url)): ?>
  <td align="center" valign="middle"><img src="<?php if(empty($picture_uri)) echo '../img/unknown.jpg'; else echo $pictureURL.$picture_uri; ?>" border="0" height="120" alt=""></td>
<?php elseif(!empty($stream_url) && $stream_type == 'mp3'): ?>
  <td width="1%" valign="middle"><img src="<?php if(empty($picture_uri)) echo '../img/unknown.jpg'; else echo $pictureURL.$picture_uri; ?>" border="0" height="120" alt=""></td>
  <td width="99%" valign="middle" align="center">
<?php else: ?>
  <td align="center">
<?php endif; ?>

<?php
if(!$isMaster && !empty($stream_url))
{
	if($stream_type == 'mp3')
	{
?>

	<embed src="<?php echo $stream_url; ?>" width="30" height="60" autostart="true" loop="false" controls="PlayOnlyButton,StopButton" type="audio/x-pn-realaudio-plugin" pluginspage="http://www.real.com/player/index.html?src=000629realhome"></embed>

<?php
	}
	elseif($stream_type == 'mp4')
	{
?>

	<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="160" height="120" type="application/x-oleobject">
	<param name="src" value="<?php echo $stream_url; ?>">
	<param name="autoplay" value="true">
	<param name="loop" value="true">
	<param name="cache" value="false">
	<embed src="<?php echo $stream_url; ?>" width="160" height="120" autoplay="true" loop="true" cache="false" type="video/quicktime" pluginspage="http://www.apple.com/quicktime/download/"></embed>
	</object>

<?php
	}
	elseif($stream_type == 'mov')
	{
?>

	<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="160" height="120" type="application/x-oleobject">
	<param name="src" value="<?php echo $stream_url; ?>">
	<param name="autoplay" value="true">
	<param name="loop" value="true">
	<param name="cache" value="false">
	<embed src="<?php echo $stream_url; ?>" width="160" height="120" autoplay="true" loop="true" cache="false" type="video/quicktime" pluginspage="http://www.apple.com/quicktime/download/"></embed>
	</object>

<?php
	}
}
?>

  </td>
</tr>
</table>

<?php if($isMaster): ?>
<br>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
  <td width="1%" valign="middle"><a href="online_links.php"><img src="../img/links.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('Links'),ENT_QUOTES,$charset); ?>"></a></td>
  <td width="49%" align="left" nowrap="nowrap">&nbsp;<a href="online_links.php"><?php echo get_lang('Links'); ?></a></td>
  <td width="49%" align="right" nowrap="nowrap"><a href="online_working_area.php" target="online_working_area"><?php echo get_lang('Home'); ?></a>&nbsp;</td>
  <td width="1%" valign="middle"><a href="online_working_area.php" target="online_working_area"><img src="../img/home.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('Home'),ENT_QUOTES,$charset); ?>"></a></td>
</tr>
</table>
<?php endif; ?>

<?php
include('footer_frame.inc.php');
?>
