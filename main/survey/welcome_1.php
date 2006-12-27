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
* 	@version $Id: welcome_1.php 10559 2006-12-27 10:52:50Z pcool $
*/

// name of the language file that needs to be included 
$language_file='survey_answer';

$cidReset=true;
session_start();
$lang = $_REQUEST['lang'];
$_SESSION["user_language_choice"]=$lang;
require_once ('../inc/global.inc.php');
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

$surveyid = $_REQUEST['surveyid'];
$uid = $_REQUEST['uid'];
$uid1 = $_REQUEST['uid1'];
$db_name = $_REQUEST['db_name'];
$temp = $_REQUEST['temp'];
$mail = $_REQUEST['mail'];
//$temp = 'template3';
//$db_name = "stableJAZ";
//$uid = 2;
//$surveyid = 1; 
$user_table = Database :: get_main_table(TABLE_MAIN_USER);
$sql_sname = "select * from $db_name.survey where survey_id='$surveyid'";
$res_sname = api_sql_query($sql_sname,__FILE__,__LINE__);
$obj_sname = mysql_fetch_object($res_sname);
$surveyname = $obj_sname->title;
$intro=$obj_sname->intro;
if(isset($_POST['Next']))
{
 $firstname = $_REQUEST['firstname'];
 $lastname = $_REQUEST['lastname'];
 $temp = $_REQUEST['temp'];
 $surveyid = $_REQUEST['surveyid'];
 $uid = $_REQUEST['uid'];
 $uid1 = $_REQUEST['uid1'];
 $db_name = $_REQUEST['db_name'];
 $email = $_REQUEST['email'];
 $organization = $_REQUEST['organization'];
 $age = $_REQUEST['age'];
 if(isset($uid))
	{$registered='Y';}
 else{$registered='N';}
 $user_table = Database :: get_main_table(TABLE_MAIN_USER);
 $survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
 if($uid1!=""){
	header("location:surveytemp_white.php?temp=$temp&surveyid=$surveyid&uid1=$uid1&db_name=$db_name&mail=$mail&lang=$lang");
	exit;
	}else{
	header("location:surveytemp_white.php?temp=$temp&surveyid=$surveyid&uid1=$uid1&db_name=$db_name&mail=$mail&lang=$lang");
	exit;
  }
}
if($uid1){
$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
$sql_u="Select * from $survey_user_info_table where id='$uid1'";
}
else{
	$sql_u="Select * from $user_table where user_id='$uid'";
}
$res = api_sql_query($sql_u,__FILE__,__LINE__);
$obj=@mysql_fetch_object($res);
$email=$obj->email;
$rs=mysql_query("select * from $db_name.questions");
$row=mysql_num_rows($rs);
$page=ceil($row/4);

if(isset($_GET[num])){
	$num=$_GET[num];
	if($num>$page){
		header("Location:test.php");
		exit;
	}
}else{
	$num = 1;
}
$lower = $num*4-4;

Display::display_header($tool_name);
?><center><?api_display_tool_title($surveyname);?></center><?
api_display_tool_title($tool_name);
if($error_message)
{
Display::display_error_message($error_message);	
}
?>
<link href="../css/survey_white.css" rel="stylesheet" type="text/css">

<table width="727" style="border: 1px solid" border="0" align="center" cellpadding="2" cellspacing="0" bgcolor="#F6F5F5">
  <tr>
    <td width="23" height="21">&nbsp;</td>
    <td height="21">&nbsp;</td>
    <td width="20" height="21">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top">
<table width="100%" height="132"  border="0" cellpadding="0" cellspacing="0" bgcolor="#F6F5F5">
		  <form method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>">
		   <!--<form method="post" action="surveytemp_white.php">-->
		    <input type="hidden" name="uid" value="<?php echo $uid;?>">
		    <input type="hidden" name="surveyid" value="<?php echo $surveyid;?>">
		    <input type="hidden" name="db_name" value="<?php echo $db_name;?>">
		    <input type="hidden" name="temp" value="<?php echo $temp;?>">
		    <input type="hidden" name="email" value="<?php echo $email;?>">
		    <input type="hidden" name="mail" value="<?php echo $mail;?>">
			<input type="hidden" name="uid1" value="<?php echo $uid1;?>">
			<input type="hidden" name="lang" value="<?php echo $lang;?>">
		    <tr>
            <td align='left'>
			<?
			echo $intro;
			?>
			</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
            <tr>
			<td align="center">
			<?php if($num > "1"){?>
			<input type="button" name="Back" value="<?php if($lang=='french')echo 'Précédent'; else if($lang=='dutch')echo 'Terug'; else echo  'Back';?>" onClick="location.href('<?php echo $_SERVER['PHP_SELF']; ?>?temp=<?php echo $temp; ?>&db_name=<?php echo $db_name; ?>&mail=<?php echo $mail; ?>&surveyid=<?php echo $surveyid; ?>&uid1=<?php echo $uid1; ?>&num=<?($num-1)?>');">
			<?
			} else{
			?>
			<input type="button" name="Back" value="<?php if($lang=='french')echo 'Précédent'; else if($lang=='dutch') echo 'Terug'; else echo 'Back';?>" onClick="location.href('template1.php?temp=<?php echo $temp; ?>&db_name=<?php echo $db_name; ?>&uid1=<?php echo $uid1; ?>&mail=<?php echo $mail; ?>&surveyid=<?php echo $surveyid; ?>');">
			<?
			}
			?>
            <input type="submit" name="Next" value="<?if($lang=='french')echo 'Suivant'; else if($lang=='dutch')echo 'Volgende'; else echo 'Next' ;?>"></td>
            </tr></form>
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
