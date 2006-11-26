<?php 
// name of the language file that needs to be included 
$language_file='survey';
$cidReset=true;
require_once ('../inc/global.inc.php');
//api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
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