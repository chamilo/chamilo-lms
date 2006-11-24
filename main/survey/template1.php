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
$lang = addslashes($_REQUEST['lang']);
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
//$tool_name=get_lang("AdministrationTools");
$surveyid = $_REQUEST['surveyid'];
$uid = $_REQUEST['uid'];
$uid1 = $_REQUEST['uid1'];
$db_name = $_REQUEST['db_name'];
$temp = $_REQUEST['temp'];
$mail = $_REQUEST['mail'];
$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);

$user_table = Database :: get_main_table(TABLE_MAIN_USER);
$sql_sname = "select * from $db_name.survey where survey_id='$surveyid'";
$res_sname = api_sql_query($sql_sname,__FILE__,__LINE__);
$obj_sname = mysql_fetch_object($res_sname);
$surveyname = $obj_sname->title;
$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);


$sql_check="SELECT 1 
			FROM $survey_user_info_table as survey_user_info
			INNER JOIN $db_name.survey as survey
				ON survey.survey_id = survey_user_info.survey_id
				AND survey.code = '".$obj_sname->code."'
			WHERE email = '$mail' AND attempted = 'yes'";

$res_check=api_sql_query($sql_check);
if(mysql_num_rows($res_check)>0)
{
	if($lang=='english')
		$error_message="You have already attempted this survey!";
	else if($lang=='french')
		$error_message="Vous avez déjà répondu à cette enquête!";
	else if($lang=='dutch')
		$error_message="U heeft deze enquête reeds ingevuld, dank u!";
	Display::display_error_message($error_message);
	exit;
}
if(isset($mail))
{

$table_reminder = Database:: get_main_table(TABLE_MAIN_SURVEY_REMINDER);
$sql_update = "UPDATE $table_reminder SET access='1' WHERE db_name='$db_name' AND sid='$surveyid' AND email='$mail'";
api_sql_query($sql_update);

}
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
 $mail = $_REQUEST['mail'];
 $organization = $_REQUEST['organization'];
 $age = $_REQUEST['age'];
 if($uid!='')
	{$registered='Y';}
 else{$registered='N';}
 $user_table = Database :: get_main_table(TABLE_MAIN_USER);
 $survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
 $sql="SELECT * FROM $survey_user_info_table WHERE email = '$mail'";
 $result=api_sql_query($sql);
 $obj=mysql_fetch_object($result);
 $new_mail=$obj->email;
 $user_id=$obj->user_id;
 if($new_mail!='')
 { 	
 $uid1=$user_id;
 $sql_ins = "update $survey_user_info_table set user_id='$uid1', survey_id='$surveyid', db_name='$db_name', firstname='$firstname', lastname='$lastname', organization='$organization', age='$age'  where user_id='$uid1'";
 $result=api_sql_query($sql_ins);
 header("location:welcome_1.php?temp=$temp&surveyid=$surveyid&uid1=$uid1&db_name=$db_name&mail=$mail&lang=$lang");
 exit;
 }
 else
 {
 $sql="SELECT max(user_id) FROM $survey_user_info_table";
 $result=api_sql_query($sql);
 $user_id=mysql_result($result, 0, max('user_id'));
 $uid1=$user_id+1;
 $sql_ins="INSERT INTO $survey_user_info_table(id,user_id,survey_id,db_name,firstname,lastname,email,organization,age, registered, attempted) values('','$uid1','$surveyid', '$db_name','$firstname','$lastname','$_REQUEST[mail]','$organization','$age','$registered', 'no')";
 $result_ins=api_sql_query($sql_ins);
 header("location:welcome_1.php?temp=$temp&surveyid=$surveyid&uid1=$uid1&db_name=$db_name&mail=$mail&lang=$lang");
 exit;
  }
}
if($uid==''){
$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
$sql_u="Select * from $survey_user_info_table where user_id='$uid1' or email='$mail'";
}
else{
	$sql_u="Select * from $user_table where user_id='$uid'";
}
$res = api_sql_query($sql_u,__FILE__,__LINE__);
$obj=@mysql_fetch_object($res);
$email=$obj->email;
$user_idd=$obj->user_id;
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
		  <form method="POST" action="<?php echo $_SERVER['PHP_SELF'];?>">
		   <!--<form method="post" action="surveytemp_white.php">-->
		    <input type="hidden" name="uid" value="<?php echo $uid;?>">
		    <input type="hidden" name="surveyid" value="<?php echo $surveyid;?>">
		    <input type="hidden" name="db_name" value="<?php echo $db_name;?>">
		    <input type="hidden" name="temp" value="<?php echo $temp;?>">
		    <input type="hidden" name="email" value="<?php echo $email;?>">
		    <input type="hidden" name="mail" value="<?php echo $mail;?>">
		    <input type="hidden" name="lang" value="<?php echo $lang;?>">
			<input type="hidden" name="uid1" value="<?php echo $uid1;?>">
		    <tr>
              <td width="32%" class="text"><?if($lang=='french')echo 'Prénom :'; else if($lang=='dutch')echo 'Voornaam :'; else echo 'First Name :' ;?></td>
              <td width="68%"><input type="text" name="firstname" maxlength="40" value="<?php echo mysql_result($res,0,"firstname");?>"></td>
            </tr>
            <tr>
              <td class="text"><?if($lang=='french')echo 'Nom :'; else if($lang=='dutch')echo 'Naam :'; else echo ' Last Name :' ;?></td>
              <td><input name="lastname" type="text" id="lastname"  maxlength="40" value="<?php echo mysql_result($res,0,"lastname");?>"></td>
            </tr>
            <tr>
               <td class="text"><?if($lang=='french')echo 'Email :'; else if($lang=='dutch')echo 'Email :'; else echo 'EMail :' ;?></td>					
			   <td  class="text"><?php echo $mail;?></td>
            </tr>
            <tr>
              <td class="text"><?if($lang=='french')echo 'Organisation :'; else if($lang=='dutch')echo 'Organisatie :'; else echo 'Organization :' ;?></td>
              <td><!--<input name="organization" type="text" id="organization"  maxlength="40" value="<?if($uid1){echo mysql_result($res,0,"organization");} else{echo "";}?>">-->
			  <?
			  if($lang=='dutch')
			  {
			  $fp=fopen("dutch_names.txt","r");
			  $names=file('dutch_names.txt');
			  }
			  else if($lang=='french')
			  {
				$fp=fopen("french_names.txt","r");
				$names=file('french_names.txt');
			  }
			  else if($lang=='english')
			  {
				$fp=fopen("all_names.txt","r");
				$names=file('all_names.txt');
			  }
			  $count=count($names);
			  ?>
			  <select name='organization'>
			  <?
			  if($uid1)
			  {
				  echo "<option>";
				  echo mysql_result($res,0,'organization');
				  echo "</option>";
			  for($i=0;$i<$count;$i++)
			  {
			  $name=$names[$i];
			  ?>
			  <option value='<?php echo $name;?>'>
			  <?php echo $names[$i];?>
			  </option>
			  <?
			  }
			  }
			  elseif($email)
			  {
			  echo "<option>";
				  echo mysql_result($res,0,'organization');
				  echo "</option>";
			  for($i=0;$i<$count;$i++)
			  {
			  $name=$names[$i];
			  ?>
			  <option value='<?php echo $name;?>'>
			  <?php echo $names[$i];?>
			  </option>
			  <?
			  }			  
			  }
			  else
			  {
			  for($i=0;$i<$count;$i++)
			  {
			  $name=$names[$i];
			  ?>
			  <option value='<?php echo $name;?>'>
			  <?php echo $names[$i];?>
			  </option><?
			  }			  
			  }
			  ?>
  			  </select></td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <?php
			if($user_idd)
			{?>
			<td><input type="submit" name="Next" value="<?if($lang=='french')echo 'Suivant'; else if($lang=='dutch')echo 'Volgende'; else echo 'Next' ;?>"></td>
			 <?php
			}
			  else
			  {
				  ?>
			  <td><br><input type="reset" value="<?if($lang=='french')echo 'Annuler'; else if($lang=='dutch')echo 'Alles wissen'; else echo ' Reset' ;?>">&nbsp;<input type="submit" name="Next" value="<?if($lang=='french')echo 'Suivant'; else if($lang=='dutch')echo 'Volgende'; else echo 'Next' ;?>"></td>
			  <?php
			  }
				  ?>
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
