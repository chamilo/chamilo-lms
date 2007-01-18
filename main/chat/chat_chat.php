<?php // $Id: chat_chat.php,v 1.10 2005/05/18 13:58:10 bvanderkimpen Exp $
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
*	Chat frame that shows the message list
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

define('FRAME','chat');

$language_file = array ('chat');
require('../inc/global.inc.php');

include(api_get_path(LIBRARY_PATH).'document.lib.php');
include (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

$reset=$_GET['reset']?true:false;

//$tbl_user=$mainDbName."`.`user";
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($query,__FILE__,__LINE__);

list($pseudoUser)=mysql_fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

/*if(!$isAllowed)
{
	exit();
}*/

$dateNow=date('Y-m-d');

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$chatPath=$documentPath.'chat_files/';

if(!is_dir($chatPath))
{
	if(is_file($chatPath))
	{
		@unlink($chatPath);
	}

	@mkdir($chatPath,0777);
	@chmod($chatPath,0777);

	$doc_id=add_document($_course,'/chat_files','folder',0,'chat_files');

	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id']);
}

if(!file_exists($chatPath.'messages-'.$dateNow.'.log'))
{
	fclose(fopen($chatPath.'messages-'.$dateNow.'.log','w'));

	$doc_id=add_document($_course,'/chat_files/messages-'.$dateNow.'.log','file',0,'messages-'.$dateNow.'.log');

	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
	item_property_update_on_folder($_course,'/chat_files', $_user['user_id']);
}

if($reset && $isMaster)
{
	$i=1;

	while(file_exists($chatPath.'messages-'.$dateNow.'-'.$i.'.log'))
	{
		$i++;
	}

	rename($chatPath.'messages-'.$dateNow.'.log',$chatPath.'messages-'.$dateNow.'-'.$i.'.log');

	fclose(fopen($chatPath.'messages-'.$dateNow.'.log','w'));

	$doc_id=add_document($_course,'/chat_files/messages-'.$dateNow.'-'.$i.'.log','file',filesize($chatPath.'messages-'.$dateNow.'-'.$i.'.log'),'messages-'.$dateNow.'-'.$i.'.log');

	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
	item_property_update_on_folder($_course,'/chat_files', $_user['user_id']);

	$doc_id = DocumentManager::get_document_id($_course,'/chat_files/messages-'.$dateNow.'.log');

	update_existing_document($_course, $doc_id,0);
}

$content=file($chatPath.'messages-'.$dateNow.'.log');
$nbr_lines=sizeof($content);
$remove=$nbr_lines-100;

if($remove < 0)
{
	$remove=0;
}

array_splice($content,0,$remove);

include('header_frame.inc.php');

if ($_GET["origin"]=='whoisonline') {  //the caller
	$content[0]=get_lang('CallSent').'<br>'.$content[0];
}
if ($_GET["origin"]=='whoisonlinejoin') {   //the joiner (we have to delete the chat request to him when he joins the chat)
	$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
	$sql="update $track_user_table set chatcall_user_id = '', chatcall_date = '', chatcall_text='' where (user_id = ".$_user['user_id'].")";
	$result=api_sql_query($sql,__FILE__,__LINE__);
}


foreach($content as $thisLine)
{
	echo "$thisLine<br>";
}

?>

<a name="bottom" style="text-decoration:none;">&nbsp;</a>

<?php
if($isMaster)
{
	$rand=mt_rand(1,1000);
?>

<br>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
  <td width="1%" valign="middle"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?rand=<?php echo $rand; ?>&reset=1#bottom" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmReset'))); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="" title="<?php echo htmlentities(get_lang('ClearList')); ?>"></a></td>
  <td width="99%">&nbsp;<a href="<?php echo $_SERVER['PHP_SELF']; ?>?rand=<?php echo $rand; ?>&reset=1#bottom" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmReset'))); ?>')) return false;"><?php echo get_lang('ClearList'); ?></a></td>
</tr>
</table>

<?php
}

include('footer_frame.inc.php');
?>
