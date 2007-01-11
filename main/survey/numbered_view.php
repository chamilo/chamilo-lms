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
* 	@version $Id: numbered_view.php 10680 2007-01-11 21:26:23Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'survey';

require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

$table_category 		= Database :: get_main_table(TABLE_MAIN_CATEGORY);
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_group 			= Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);

$tool_name = get_lang('ViewQuestions');
$header2 = get_lang('GroupName');
$header3 = get_lang('Type');
$ques_id = $_GET['qid'];
$gname=surveymanager::ques_id_group_name($ques_id);
$ques_type = $_GET['qtype'];
$sql = "SELECT * FROM $db_name.questions where qid='$ques_id'";
$res = api_sql_query($sql);
$obj = mysql_fetch_object($res);	
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>White Template</title>
<link href="../survey/css_white/style.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.style5 {font-size: 12px; font-weight: bold; font-family: Arial, Helvetica, sans-serif;}
-->
</style>
</head>

<body>
<table width="100%" cellpadding="0" cellspacing="1" bordercolor="#000000" bgcolor="#000000">
  <tr>
    <td width="66%" bgcolor="#FFFFFF" class="bg-3">&nbsp;&nbsp;<span class="text">My Portal &gt; Course Home &gt; Survey</span></td>
    <td width="34%" align="right" bgcolor="#FFFFFF" class="bg-3"><span class="text"></span>&nbsp;</td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td colspan="2" align="center" valign="top"><br>
      <br>
      <table width="600" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="23" height="21"><img src="../survey/images_white/top-1.gif" width="23" height="21"></td>
          <td height="21" background="../survey/images_white/top-2.gif">&nbsp;</td>
          <td width="20" height="21"><img src="../survey/images_white/top-3.gif" width="20" height="21"></td>
        </tr>
        <tr>
		<td height="39" background="../survey/images_white/left.gif">&nbsp;</td>
        <td><strong>Question: </strong><br><?php echo $obj->caption;?><br><br><strong>Answers: </strong><br>
		<?php
		for($i=1;$i<10;$i++)
		{
			$temp = "a".$i;
			if(empty($obj->$temp))
				break;
			else
			{
		?>
		<textarea cols="50" rows="3" disabled="true"><?php echo $obj->$temp; ?></textarea>
		<select>
		<option value="$i">1</option>
		<option value="$i">2</option>
		<option value="$i">3</option>
		<option value="$i">4</option>
		<option value="$i">5</option>
		<option value="$i">6</option>
		<option value="$i">7</option>
		<option value="$i">8</option>
		<option value="$i">9</option>
		<option value="$i">10</option>
		</select><br>
		<?php
			}
		}
		?>
		</td>
        <td background="../survey/images_white/right.gif">&nbsp;</td>
		</tr>
        <tr>
          <td background="../survey/images_white/left.gif">&nbsp;</td>
          <td><p>&nbsp;</p>
              </td>
          <td background="../survey/images_white/right.gif">&nbsp;</td>
        </tr>
        <tr>
          <td><img src="../survey/images_white/bottom-1.gif" width="23" height="21"></td>
          <td background="../survey/images_white/bottom-2.gif">&nbsp;</td>
          <td><img src="../survey/images_white/bottom-3.gif" width="20" height="21"></td>
        </tr>
      </table>
      <p><br>
        <br>
      </p>
    <p>&nbsp;</p>
    <p>&nbsp;           </p></td>
  </tr>
  <tr align="right" bgcolor="#FFFFFF">
    <td height="30" align="left" class="bg-4">&nbsp;&nbsp;<span class="text">Manager : </span><span class="text-2">user admin</span> </td>
    <td height="30" class="bg-4"><span class="text">Platform</span> <span class="text-2">Dokeos 1.6.3</span> <span class="text">&copy; 2006&nbsp;&nbsp;</span></td>
  </tr>
</table>
</body>
</html>
