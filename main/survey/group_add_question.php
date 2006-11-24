<?php
// $Id: course_add.php,v 1.10 2005/05/30 11:46:48 bmol Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
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
*	@package dokeos.admin
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = 'survey';

require_once ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
$cidReq=$_GET['cidReq'];
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
//$table_survey = Database :: get_main_table(MAIN_SURVEY_IFA_TABLE);
$tool_name1 = get_lang('AddQuestion1');
$tool_name = get_lang('AddQuestion');
$interbredcrump[] = array ("url" => "survey.php", "name" => get_lang('CreateSurvey'));
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$surveyid=$_GET['surveyid'];
$groupid = $_GET['groupid'];
if (isset($_POST['back']))
{
	$surveyid=$_POST['surveyid'];
	$groupid=$_POST['groupid'];	
	$cidReq=$_REQUEST['cidReq'];
	header("Location:create_new_group.php?surveyid=$surveyid&cidReq=$cidReq");
	exit;
}
if(isset($_POST['next']))
{	
	$cidReq=$_REQUEST['cidReq'];
	if($_POST['radiobutton']=="1")
	{
	    $surveyid=$_POST['surveyid'];
		$groupid=$_POST['groupid'];		
		header("Location:select_question_type.php?surveyid=$surveyid&groupid=$groupid&cidReq=$cidReq");
		exit;
	}
	elseif($_POST['radiobutton']=="2")
	{
		header("Location:question_type.php");
	}
	
	else
	{	
        $sid=$_POST['newsurveyid'];
		$gid=$_POST['newgroupid'];	
		header("Location:group_list.php?surveyid=$surveyid&groupid=$groupid&cidReq=$cidReq");
	}
}
Display::display_header($tool_name1);
$GName = get_lang('groupname');
api_display_tool_title($tool_name);
$name = surveymanager :: get_groupname($gid);
?>
<form name="radiobutton" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $_REQUEST['cidReq']; ?>">-->
<table>
<tr>
<td><?php api_display_tool_title($GName);?></td> 
<td><?php api_display_tool_title($name);?></td>
</tr>
<?
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}

?>
<tr>
	<td>
		<input name="radiobutton" type="radio" value="1" checked><?php echo get_lang('CreateNew');?>
	</td>
</tr>
<tr>
</tr>
<tr>
</tr>
<tr>
	<td>
		<strong><?php echo get_lang('GetFromDB');?></strong>
	</td>
</tr>
<tr>
	<td>
		<input name="radiobutton" type="radio" value="2"><?php echo get_lang('ByQuestion');?>
	</td>	
</tr>
<tr>
	<td>
		<input name="radiobutton" type="radio" value="3"><?php echo get_lang('ByGroup');?>
	</td>
</tr>
<tr>
  <td></td>
  <td>&nbsp;</td>
</tr>
<tr>
<td>
<input type="submit" name= 'back' value="<?php echo get_lang('back');?>">
<input type="submit" name= 'next' value="<?php echo get_lang('next');?>"></td>
</tr>
</table>
</form>
<?php
function g_redirect($url,$mode)
/*  It redirects to a page specified by "$url".
 *  $mode can be:
 *    LOCATION:  Redirect via Header "Location".
 *    REFRESH:  Redirect via Header "Refresh".
 *    META:      Redirect via HTML META tag
 *    JS:        Redirect via JavaScript command
 */
{
  if (strncmp('http:',$url,5) && strncmp('https:',$url,6)) {

     $starturl = ($_SERVER["HTTPS"] == 'on' ? 'https' : 'http') . '://'.
                 (empty($_SERVER['HTTP_HOST'])? $_SERVER['SERVER_NAME'] :
                 $_SERVER['HTTP_HOST']);

     if ($url[0] != '/') $starturl .= dirname($_SERVER['PHP_SELF']).'/';

     $url = "$starturl$url";
  }
  switch($mode) {
     case 'LOCATION': 
       if (headers_sent()) exit("Headers already sent. Can not redirect to $url");
       header("Location: $url");
       exit;
     case 'REFRESH': 
       if (headers_sent()) exit("Headers already sent. Can not redirect to $url");
       header("Refresh: 0; URL=\"$url\""); 
       exit;
     case 'META':
       ?><meta http-equiv="refresh" content="0;url=<?php echo $url; ?>" /><?
       exit;
     default: /* -- JavaScript */
       ?><script type="text/javascript">
       window.location.href='<?php echo $url; ?>';
       </script><?
  }
  exit;
} 
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>