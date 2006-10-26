<?php // $Id: index.php,v 1.44 2005/07/01 10:03:36 olivierb78 Exp $
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
*	Index of the admin tools
*
*	@package dokeos.admin
==============================================================================
*/
$langFile='survey';
$cidReset=true;
session_start();
$_SESSION["user_language_choice"]='english';
require_once ('../inc/global.inc.php');
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
//$tool_name=get_lang("AdministrationTools");
$surveyid = $_REQUEST['surveyid'];
$uid = $_REQUEST['uid'];
$uid1 = $_REQUEST['uid1'];
$db_name = $_REQUEST['db_name'];
$temp = $_REQUEST['temp'];
$mail = $_REQUEST['mail'];


if(isset($_GET['next'])){
	
	$lang = $_REQUEST['lang'];
	$code_survey = $_REQUEST['code_survey'];
	
	$sql = 'SELECT survey.survey_id 
			FROM '.$db_name.'.survey as survey 
			WHERE survey.code="'.addslashes($code_survey).'" 
			AND survey.lang="'.addslashes($lang).'"';
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$surveyid = mysql_result($rs,0,'survey_id');
	
	header('Location:template1.php?'.$_SERVER['QUERY_STRING'].'&surveyid='.$surveyid);
}

$sql_sname = "	SELECT survey2.* FROM $db_name.survey as survey1
				INNER JOIN $db_name.survey as survey2
					ON survey1.code = survey2.code
				WHERE survey1.survey_id='$surveyid'";

$res_sname = api_sql_query($sql_sname,__FILE__,__LINE__);

$code_survey = mysql_result($res_sname, 0, 'code');
mysql_data_seek($res_sname,0);

$surveyname = '';
while($obj_sname = mysql_fetch_object($res_sname)){
	$surveyname .= $obj_sname->title.'<br>';
}
mysql_data_seek($res_sname,0);

Display::display_header($tool_name);
?><center><?api_display_tool_title($surveyname);?></center><?
api_display_tool_title($tool_name);
if($error_message)
{
Display::display_error_message($error_message);	
}
?>
<link href="../css/survey_white.css" rel="stylesheet" type="text/css">

<table width="727" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
  <tr>
    <td width="23" height="21">&nbsp;</td>
    <td height="21">&nbsp;</td>
    <td width="20" height="21">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top">
    <table width="100%" height="132"  border="0" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
		  <form method="GET" action="<?php echo $_SERVER['PHP_SELF'] ?>">
		    <input type="hidden" name="uid" value="<?=$uid;?>">
		    <input type="hidden" name="db_name" value="<?=$db_name;?>">
		    <input type="hidden" name="temp" value="<?=$temp;?>">
		    <input type="hidden" name="email" value="<?=$email;?>">
		    <input type="hidden" name="mail" value="<?=$mail;?>">
			<input type="hidden" name="uid1" value="<?=$uid1;?>">
			<input type="hidden" name="code_survey" value="<?=$code_survey;?>">
			Select in which language you want to see this survey : 
			<select name="lang">
			<?php
			while($survey = mysql_fetch_object($res_sname)){				
				echo '<option value="'.$survey->lang.'">'.$survey->lang.'</option>';
			}
			?>
			</select>
			<br /><br />
			<input type="submit" name="next" value="<?php echo get_lang('Next') ?>" />
		  </form>
    </table>
	</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
