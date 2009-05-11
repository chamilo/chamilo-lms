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
*	Chat frame that shows the message list
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','chat');

// name of the language file that needs to be included 
$language_file='chat';

include('../inc/global.inc.php');

$reset=$_GET['reset']?true:false;

$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($query,__FILE__,__LINE__);

list($pseudoUser)=mysql_fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed)
{
	exit();
}

$dateNow=date('Y-m-d');

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
$onlinePath=$documentPath.'online_files/';

if(!file_exists($onlinePath.'messages-'.$dateNow.'.log'))
{
	fclose(fopen($onlinePath.'messages-'.$dateNow.'.log','w'));
}

if($reset && $isMaster)
{
	$i=1;

	while(file_exists($onlinePath.'messages-'.$dateNow.'-'.$i.'.log'))
	{
		$i++;
	}

	rename($onlinePath.'messages-'.$dateNow.'.log',$onlinePath.'messages-'.$dateNow.'-'.$i.'.log');

	fclose(fopen($onlinePath.'messages-'.$dateNow.'.log','w'));
}

$content=file($onlinePath.'messages-'.$dateNow.'.log');
$nbr_lines=sizeof($content);
$remove=$nbr_lines-100;

if($remove < 0)
{
	$remove=0;
}

array_splice($content,0,$remove);

include('header_frame.inc.php');

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
  <td width="1%" valign="middle"><a href="<?php echo api_get_self(); ?>?rand=<?php echo $rand; ?>&reset=1#bottom" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmReset'),ENT_QUOTES,$charset)); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="" title="<?php echo api_htmlentities(get_lang('ClearList'),ENT_QUOTES,$charset); ?>"></a></td>
  <td width="99%">&nbsp;<a href="<?php echo api_get_self(); ?>?rand=<?php echo $rand; ?>&reset=1#bottom" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmReset'),ENT_QUOTES,$charset)); ?>')) return false;"><?php echo get_lang('ClearList'); ?></a></td>
</tr>
</table>

<?php
}

include('footer_frame.inc.php');
?>
