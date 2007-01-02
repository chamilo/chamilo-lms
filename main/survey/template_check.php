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
* 	@version $Id: template_check.php 10584 2007-01-02 15:09:21Z pcool $
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

$un = $_REQUEST['un'];
$survey_user_info_table = Database :: get_main_table(TABLE_MAIN_SURVEY_USER);
$sql="SELECT * FROM $survey_user_info_table WHERE user_number = '$un'";
$result=api_sql_query($sql);
$obj=mysql_fetch_object($result);
$info_id=$obj->id;
$surveyid=$obj->survey_id;
$db_name=$obj->db_name;
$new_mail=$obj->email;
$sql_temp = "SELECT * FROM $db_name.survey WHERE survey_id='$surveyid'";
$res_temp = api_sql_query($sql_temp, __FILE__, __LINE__);
$obj_temp=@mysql_fetch_object($res_temp);
$template=$obj_temp->template;
switch($template){
	case template1:
           header("location:template1.php?temp=$template&surveyid=$surveyid&mail=$new_mail&db_name=$db_name");
	       break;
    case template2:
           header("location:template2.php?temp=$template&surveyid=$surveyid&mail=$new_mail&db_name=$db_name");
	       break;
	case template3:
           header("location:template3.php?temp=$template&surveyid=$surveyid&mail=$new_mail&db_name=$db_name");
	       break;
    case template4:
           header("location:template4.php?temp=$template&surveyid=$surveyid&mail=$new_mail&db_name=$db_name");
	       break;
    case template5:
           header("location:template5.php?temp=$template&surveyid=$surveyid&mail=$new_mail&db_name=$db_name");
	       break;
}

?>