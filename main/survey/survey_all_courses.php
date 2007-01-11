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
* 	@author unknown
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts (if not all) of the code
* 	@version $Id: survey_all_courses.php 10680 2007-01-11 21:26:23Z pcool $
* 
* 	@todo 	check if this page has to display all the shared surveys of the courses where the user is a teacher or only 
* 			the shared surveys of this course?
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

// Database table definitions
/** @todo use database constants for the survey tables */
$table_survey 				= Database :: get_course_table(TABLE_SURVEY);
$table_group 				= Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question		= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course_survey_rel 	= Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);

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

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

// Displaying the header
Display::display_header(get_lang('CreateFromExistingSurveys'));

// Displaying the tool title
//api_display_tool_title(get_lang('CreateFromExistingSurveys'));

?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="add_survey">
<input type="hidden" name="surveyid" value="<?php echo $_GET['survey_id']; ?>">
<?php 	
		$rsDbs = mysql_list_dbs();
		$db_list = array();
		while($db = mysql_fetch_array($rsDbs))
		{
			$db_list[] = $db[0];
		}
   		$nameTools=get_lang('CreateFromExistingSurveys');
		$table_group = Database :: get_course_table(TABLE_SURVEY_GROUP);
		$sql = "SELECT course_survey.*, visual_code 
				FROM $table_course_survey_rel as course_survey
				INNER JOIN ".Database::get_main_table(TABLE_MAIN_COURSE)." as course
				ON course_survey.course_code = course.code";
		$parameters = array ();
		$parameters['surveyid']=$_GET['survey_id'];
		$parameters['newgroupid']=$groupid;
		$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_num_rows($res) > 0)
	{		
		$surveys = array ();
		while ($obj = mysql_fetch_object($res))
		{
			$db_name = $obj->db_name;
			$course_name = $obj->visual_code;
			$survey_id = $obj->survey_id;
			//echo "<pre>";
			
			if(in_array($db_name, $db_list)){
			
				$sql_survey = "SELECT * FROM $db_name.survey WHERE survey_id = '$survey_id' AND is_shared='1'";
				
				//echo "</pre>";
				$res_survey = api_sql_query($sql_survey,__FILE__,__LINE__);
				$survey = array ();
				while($object=mysql_fetch_object($res_survey))
				{
					//$survey[] = '<input type="checkbox" name="course[]" value="'.$obj->group_id.'">';
					$survey[] = $object->title;
					//$groupid=$obj->group_id;
					$authorid=surveymanager::get_author($db_name,$survey_id);
					$author=surveymanager::get_survey_author($authorid);
					//$NoOfQuestion=surveymanager::no_of_question($groupid);
					$survey[] = $author;
					$survey[] = $course_name;
					$survey[] = $object->lang;
					$survey[] = $object->avail_from ;
					$survey[] = $object->avail_till ;	
					$survey[] = "<a href=create_from_existing_survey.php?surveyid=$survey_id&db_name=$db_name><img src=\"../img/info_small.gif\" border=\"0\" align=\"absmiddle\" alt=view></a>";
					$surveys[] = $survey;				
				}
				
			}
        }
		$table_header[] = array (get_lang('SurveyName'), true);
		$table_header[] = array (get_lang('Author'), true);
		$table_header[] = array (get_lang('CourseName'), true);
		$table_header[] = array (get_lang('Language'), true);
		$table_header[] = array (get_lang('AvailableFrom'), true);
		$table_header[] = array (get_lang('AvailableTill'), true);		
		$table_header[] = array (' ', false);
		if(!empty($surveys))
		{
			Display :: display_sortable_table($table_header, $surveys, array (), array (), $parameters);
		}
		else
		{
			$flag=1;
		}
		?>		
		</form>
<?php	
    }
	else
	{
		echo get_lang('NoSearchResults');
	}
	if($flag=='1')
	{
		echo get_lang('SurveyNotShared');
	}
	?>
	<form action="survey_list.php" method="post">
    <input type="submit" name="back1" value="<?php echo get_lang('Back'); ?>">
    </form>
<?php
/*
-----------------------------------------------------------
	Footer
-----------------------------------------------------------
*/
Display :: display_footer();
?> 




