<?php // $Id: online_links.php 20467 2009-05-11 08:38:29Z ivantcholakov $
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
*	Management of links. When we click on a link, it opens in the right frame
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','links');

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

$action=$_GET['action'];
$link=intval($_GET['link']);

$tbl_user=Database::get_main_table(TABLE_MAIN_USER);
$tbl_online_link=Database::get_course_table(TABLE_ONLINE_LINK);

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=Database::query($query,__FILE__,__LINE__);

list($pseudoUser)=Database::fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed || !$isMaster)
{
	exit();
}

$doc_path=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';

if($_POST['sent'])
{
	$sent=1;

	$link_name=trim(stripslashes($_POST['link_name']));
	$link_url=trim(stripslashes($_POST['link_url']));
	$link_file=$_FILES['link_file'];

	if($link_file['size'])
	{
		if(empty($link_name))
		{
			$link_name=$link_file['name'];
		}

		$link_file['name']=php2phps(replace_dangerous_char($link_file['name'],'strict'));

		$extension=explode('.',$link_file['name']);

		$extension=$extension[sizeof($extension)-1];

		$suffix='';
		$i=0;

		do
		{
			if(file_exists($doc_path.str_replace('.'.$extension,$suffix.'.'.$extension,$link_file['name'])))
			{
				$suffix='_'.(++$i);
			}
			else
			{
				break;
			}
		}
		while(1);

		$link_file['name']=str_replace('.'.$extension,$suffix.'.'.$extension,$link_file['name']);

		move_uploaded_file($link_file['tmp_name'],$doc_path.$link_file['name']);

		$link_url=str_replace($_configuration['root_sys'],$_configuration['root_web'],$doc_path).$link_file['name'];
	}

	if(!empty($link_name) && !empty($link_url))
	{
		if(!strstr($link_url,'://'))
		{
			$link_url='http://'.$link_url;
		}

		if($action == 'edit')
		{
			$query="UPDATE $tbl_online_link
					SET name='".addslashes($link_name)."',
						url='".addslashes($link_url)."'
					WHERE id='$link'";
			Database::query($query,__FILE__,__LINE__);
		}
		else
		{
			$query="INSERT INTO $tbl_online_link (name,url) VALUES('".addslashes($link_name)."','".addslashes($link_url)."')";
			Database::query($query,__FILE__,__LINE__);
		}
	}

	mysql_close();
	header('Location: '.api_get_self());
	exit();
}

if($action == 'delete')
{
	$link=intval($_GET['link']);

	$query="DELETE FROM $tbl_online_link WHERE id='$link'";
	Database::query($query,__FILE__,__LINE__);

	mysql_close();
	header('Location: '.api_get_self());
	exit();
}

$query="SELECT id,name,url FROM $tbl_online_link ORDER BY name";
$result=Database::query($query,__FILE__,__LINE__);

$Links=array();

while($row=Database::fetch_array($result))
{
	$Links[]=$row;

	if($action == 'edit' && $link == $row['id'])
	{
		$link_name=$row['name'];
		$link_url=$row['url'];
	}
}

if($action == 'edit' && empty($link_name))
{
	$action='';
	$link=0;
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

<form method="post" action="<?php echo api_get_self(); ?>?action=<?php echo $action; ?>&link=<?php echo $link; ?>" enctype="multipart/form-data">
<input type="hidden" name="sent" value="1">
<table border="0" cellpadding="3" cellspacing="0">
<tr>
  <td width="45%"><?php echo get_lang('LinkName'); ?> :</td>
  <td width="55%"><input type="text" name="link_name" size="10" maxlength="50" value="<?php if($action == 'edit') echo api_htmlentities($link_name,ENT_QUOTES,$charset); ?>" style="width: 95px;"></td>
</tr>
<tr>
  <td width="45%"><?php echo get_lang('LinkURL'); ?> :</td>
  <td width="55%"><input type="text" name="link_url" size="10" maxlength="100" value="<?php if($action == 'edit') echo htmlentities($link_url); else echo 'http://'; ?>" style="width: 95px;"></td>
</tr>
<tr>
  <td width="45%"><?php echo get_lang('OrFile'); ?> :</td>
  <td width="55%"><input type="file" name="link_file" size="1" value="" style="width: 95px;"></td>
</tr>
<tr>
  <td colspan="2" align="center">
	<input type="submit" value="<?php echo api_htmlentities('  '.get_lang('Ok').'  ',ENT_QUOTES,$charset); ?>">
  </td>
</tr>
</table>
</form>

<br>

<?php
if(!sizeof($Links))
{
	echo get_lang('NoLinkAvailable');
}
else
{
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">

<?php
	foreach($Links as $enreg)
	{
?>

<tr>
  <td width="98%"><a href="online_goto.php?url=<?php echo urlencode($enreg['url']); ?>" target="online_working_area"><?php echo $enreg['name']; ?></a></td>
  <td width="1%" valign="middle"><a href="<?php echo api_get_self(); ?>?action=edit&link=<?php echo $enreg['id']; ?>"><img src="../img/edit.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('Modify'),ENT_QUOTES,$charset); ?>"></a></td>
  <td width="1%" valign="middle"><a href="<?php echo api_get_self(); ?>?action=delete&link=<?php echo $enreg['id']; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('Delete'),ENT_QUOTES,$charset); ?>"></a></td>
</tr>

<?php
	}

	unset($Links);
?>

</table>

<?php
}

include('footer_frame.inc.php');
?>
