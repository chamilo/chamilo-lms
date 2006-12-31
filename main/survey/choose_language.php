<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author 
* 	@version $Id: choose_language.php 10578 2006-12-31 17:01:58Z pcool $
* 	@todo check if this file is used
*/

// name of the language file that needs to be included 
$language_file='survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

// Database table definitions
/** @todo use database constants for the survey tables */

// Path variables
/** @todo these variables are probably not used here */

// breadcrumbs

// $_GET and $_POST
/** @todo replace $_REQUEST with $_GET or $_POST */
$surveyid = $_REQUEST['surveyid'];
$uid = $_REQUEST['uid'];
$uid1 = $_REQUEST['uid1'];
$db_name = $_REQUEST['db_name'];
$temp = $_REQUEST['temp'];
$mail = $_REQUEST['mail'];


/** @todo is this needed? Session probably started in global.inc.php */
session_start();

/** @todo is this needed? */
$_SESSION["user_language_choice"]='english';




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

/** @todo use correct database calls */
$sql_sname = "	SELECT survey2.* FROM $db_name.survey as survey1
				INNER JOIN $db_name.survey as survey2
					ON survey1.code = survey2.code
				WHERE survey1.survey_id='$surveyid'";
$res_sname = api_sql_query($sql_sname,__FILE__,__LINE__);
$code_survey = mysql_result($res_sname, 0, 'code');
mysql_data_seek($res_sname,0);

$surveyname = '';
while($obj_sname = mysql_fetch_object($res_sname))
{
	$surveyname .= $obj_sname->title.'<br>';
}
mysql_data_seek($res_sname,0);

Display::display_header($tool_name);
api_display_tool_title($surveyname);
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
		    <input type="hidden" name="uid" value="<?php echo $uid;?>">
		    <input type="hidden" name="db_name" value="<?php echo $db_name;?>">
		    <input type="hidden" name="temp" value="<?php echo $temp;?>">
		    <input type="hidden" name="email" value="<?php echo $email;?>">
		    <input type="hidden" name="mail" value="<?php echo $mail;?>">
			<input type="hidden" name="uid1" value="<?php echo $uid1;?>">
			<input type="hidden" name="code_survey" value="<?php echo $code_survey;?>">
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
// Display the footer
Display::display_footer();
?>
