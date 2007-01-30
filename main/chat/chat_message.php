<?php // $Id: chat_message.php,v 1.11 2005/05/18 13:58:20 bvanderkimpen Exp $
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
*	Allows to type the messages that will be displayed on chat_chat.php
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

define('FRAME','message');

$language_file = array ('chat');

require('../inc/global.inc.php');
api_protect_course_script();

include_once(api_get_path(LIBRARY_PATH).'document.lib.php');
include_once(api_get_path(LIBRARY_PATH).'text.lib.php');
include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$tbl_user	= Database::get_main_table(TABLE_MAIN_USER);
$sent = $_REQUEST['sent'];
$question = $_REQUEST['question'];

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($query,__FILE__,__LINE__);

list($pseudoUser)=mysql_fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

/*if(!$isAllowed)
{
	exit();
}*/

$timeNow=date('H:i');
$dateNow=date('Y-m-d');

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$chatPath=$documentPath.'chat_files/';
$TABLEITEMPROPERTY= Database::get_course_table(TABLE_ITEM_PROPERTY);

if(!is_dir($chatPath))
{
	if(is_file($chatPath))
	{
		@unlink($chatPath);
	}

	@mkdir($chatPath,0777);
	@chmod($chatPath,0777);

	$doc_id=add_document($_course,'/chat_files','folder',0,'chat_files');

	api_sql_query("INSERT INTO ".$TABLEITEMPROPERTY . " (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$doc_id,'DocumentAdded',1,0,NULL,0)");
	
}

$chat_size=0;

if($sent)
{
	$message=trim(htmlspecialchars(stripslashes($_POST['message'])));

	if(!empty($message))
	{
		$message=make_clickable($message);

		if($question)
		{
			$message='<span class="question"><b>'.get_lang('Question').' :</b> '.$message.'</span>';
		}

		if(!file_exists($chatPath.'messages-'.$dateNow.'.log'))
		{
			$doc_id=add_document($_course,'/chat_files/messages-'.$dateNow.'.log','file',0,'messages-'.$dateNow.'.log');

			api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
			item_property_update_on_folder($_course,'/chat_files', $_user['user_id']);
		}
		else
		{
			$doc_id = DocumentManager::get_document_id($_course,'/chat_files/messages-'.$dateNow.'.log');
		}

		$fp=fopen($chatPath.'messages-'.$dateNow.'.log','a');

		if($isMaster)
		{
			fputs($fp,"[$timeNow] <span class=\"master\"><b>$pseudoUser</b></span> : $message\n");
		}
		else
		{
			fputs($fp,"[$timeNow] <b>$pseudoUser</b> : $message\n");
		}

		fclose($fp);

		$chat_size=filesize($chatPath.'messages-'.$dateNow.'.log');

		update_existing_document($_course, $doc_id,$chat_size);
		item_property_update_on_folder($_course,'/chat_files', $_user['user_id']);
	}
}

include('header_frame.inc.php');
?>

<form name="formMessage" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="javascript:if(document.formMessage.message.value == '') { alert('<?php echo addslashes(htmlentities(get_lang('TypeMessage'))); ?>'); document.formMessage.message.focus(); return false; }" autocomplete="off">
<input type="hidden" name="sent" value="1">
<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td width="95%"><input type="text" name="message" size="50" value="" style="width: 100%;"></td>
  
  <td width="5%"><input type="submit" value="OK" style="width: 30px;"></td>
</tr>
</table>
</form>

<?php
include('footer_frame.inc.php');
?>