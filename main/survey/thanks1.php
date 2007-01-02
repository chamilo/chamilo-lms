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
* 	@version $Id: thanks1.php 10584 2007-01-02 15:09:21Z pcool $
*/

// name of the language file that needs to be included 
$language_file='survey_answer';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

$surveyid = $_REQUEST['surveyid'];
$uid1 = $_REQUEST['uid1'];
$db_name = $_REQUEST['db_name'];
$temp = $_REQUEST['temp'];
$user_table = Database :: get_main_table(TABLE_MAIN_USER);
$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
$sql_uname= "SELECT * FROM $survey_user_info_table WHERE id = $uid1";
$res_uname = api_sql_query($sql_uname,__FILE__,__LINE__);
$obj= @mysql_fetch_object($res_uname);
$username = $obj->firstname;
$sql_sname= "SELECT * FROM $db_name.survey WHERE survey_id = $surveyid";
$res_sname = api_sql_query($sql_sname,__FILE__,__LINE__);
$obj_sname= @mysql_fetch_object($res_sname);
$surveyname = $obj_sname->title;
$lang = $obj_sname->lang;
Display::display_header($tool_name);
api_display_tool_title($tool_name);	
?>

<link href="../css/survey_white.css" rel="stylesheet" type="text/css">

<table width="600" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">

<table width="727" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
  <tr>
    <td width="23" height="21" class="form-top-1">&nbsp;</td>
    <td height="21" class="form-top-2">&nbsp;</td>
    <td width="20" height="21" class="form-top-3">&nbsp;</td>
  </tr>
  <tr>
    <td class="form-left">&nbsp;</td>
    <td valign="top"><table width="100%" height="132"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td colspan="2" align="center" class="text">
			 <br>
			 <?php echo $obj_sname->surveythanks;?>
			 <br>
			</td>
      </tr>
      <tr>
        <td colspan="2" align="center" class="text"><input type="button" name="exit" value="<?php if($lang=='french')echo 'Quitter'; else if($lang=='dutch') echo 'Verlaten'; else echo 'Exit';?>" onclick="document.location.href='<?php echo api_get_path(WEB_PATH) ?>'">
        </td>
      </tr>
    </table></td>
    <td class="form-right">&nbsp;</td>
  </tr>
  <tr>
    <td class="form-bottom-1">&nbsp;</td>
    <td class="form-bottom-2">&nbsp;</td>
    <td class="form-bottom-3">&nbsp;</td>
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
