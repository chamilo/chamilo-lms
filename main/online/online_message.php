<?php // $Id: online_message.php 20467 2009-05-11 08:38:29Z ivantcholakov $
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
*	Allows to type the messages that will be displayed on online_chat.php
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','message');

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;


/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$sent = $_REQUEST['sent'];
$question = $_REQUEST['question'];

$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=Database::query($query,__FILE__,__LINE__);

list($pseudoUser)=mysql_fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed)
{
	exit();
}

$timeNow=date('H:i');
$dateNow=date('Y-m-d');

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

$chat_size=0;

if($sent)
{
	$message=trim(htmlspecialchars(stripslashes($_POST['message']),ENT_QUOTES,$charset));

	if(!empty($message))
	{
		$message=make_clickable($message);

		if($question)
		{
			$message='<span class="question"><b>'.get_lang('Question').' :</b> '.$message.'</span>';
		}

		$fp=fopen($onlinePath.'messages-'.$dateNow.'.log','a');

		if($isMaster)
		{
			fputs($fp,"[$timeNow] <span class=\"master\"><b>$pseudoUser</b></span> : $message\n");
		}
		else
		{
			fputs($fp,"[$timeNow] <b>$pseudoUser</b> : $message\n");
		}

		fclose($fp);

		$chat_size=filesize($onlinePath.'messages-'.$dateNow.'.log');
	}
}

include('header_frame.inc.php');
?>

<form name="formMessage" method="post" action="<?php echo api_get_self(); ?>" onsubmit="javascript:if(document.formMessage.message.value == '') { alert('<?php echo addslashes(api_htmlentities(get_lang('TypeMessage'),ENT_QUOTES,$charset)); ?>'); document.formMessage.message.focus(); return false; }" autocomplete="off">
<input type="hidden" name="sent" value="1">
<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
  <td width="90%"><input type="text" name="message" size="50" value="" style="width: 100%;"></td>
  <td width="9%" nowrap="nowrap"><?php echo get_lang('Question'); ?>&nbsp;<input type="checkbox" name="question" value="1" style="vertical-align: middle;" onclick="javascript:if(this.checked == true && !confirm('<?php echo addslashes(api_htmlentities(get_lang('OnlyCheckForImportantQuestion'),ENT_QUOTES,$charset)); ?>')) this.checked=false; document.formMessage.message.focus();"></td>
  <td width="1%"><input type="submit" value="OK" style="width: 30px;"></td>
</tr>
</table>
</form>

<?php
include('footer_frame.inc.php');
?>
