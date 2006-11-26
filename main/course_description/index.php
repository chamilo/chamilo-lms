<?php // $Id: index.php 10204 2006-11-26 20:46:53Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This script edits the course description.
*	This script is reserved for users with write access on the course.
*
*	@author Thomas Depraetere
*	@author Hugues Peeters
*	@author Christophe Gesché
*	@author Olivier brouckaert
*	@package dokeos.course_description
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array ('course_description', 'pedaSuggest');

include ('../inc/global.inc.php');
$this_section = SECTION_COURSES;

include (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
Display :: display_header($nameTools, "Description");
api_display_tool_title($nameTools);

api_protect_course_script();


/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$nameTools = get_lang(TOOL_COURSE_DESCRIPTION);

$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('CourseProgram'));

$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
$show_description_list = true;
$show_peda_suggest = true;
define('ADD_BLOCK', 0);
// Default descriptions
$default_description_titles = array();
$default_description_titles[1]= get_lang('GeneralDescription');
$default_description_titles[2]= get_lang('Objectives');
$default_description_titles[3]= get_lang('Topics');
$default_description_titles[4]= get_lang('Methodology');
$default_description_titles[5]= get_lang('CourseMaterial');
$default_description_titles[6]= get_lang('HumanAndTechnicalResources');
$default_description_titles[7]= get_lang('Assessment');
$question = array();
$question[1]= get_lang('GeneralDescriptionQuestions');
$question[2]= get_lang('ObjectivesQuestions');
$question[3]= get_lang('TopicsQuestions');
$question[4]= get_lang('MethodologyQuestions');
$question[5]= get_lang('CourseMaterialQuestions');
$question[6]= get_lang('HumanAndTechnicalResourcesQuestions');
$question[7]= get_lang('AssessmentQuestions');
$information = array();
$information[1]= get_lang('GeneralDescriptionInformation');
$information[2]= get_lang('ObjectivesInformation');
$information[3]= get_lang('TopicsInformation');
$information[4]= get_lang('MethodologyInformation');
$information[5]= get_lang('CourseMaterialInformation');
$information[6]= get_lang('HumanAndTechnicalResourcesInformation');
$information[7]= get_lang('AssessmentInformation');
$default_description_title_editable = array();
$default_description_title_editable[1] = false;
$default_description_title_editable[2] = true;
$default_description_title_editable[3] = true;
$default_description_title_editable[4] = true;
$default_description_title_editable[5] = true;
$default_description_title_editable[6] = true;
$default_description_title_editable[7] = true;


/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$description_id = isset ($_REQUEST['description_id']) ? intval($_REQUEST['description_id']) : null;
if (api_is_allowed_to_edit() && !is_null($description_id))
{
	// Delete a description block
	if (isset ($_GET['action']) && $_GET['action'] == 'delete')
	{
		$sql = "DELETE FROM $tbl_course_description WHERE id='$description_id'";
		api_sql_query($sql, __FILE__, __LINE__);
		Display :: display_normal_message(get_lang('CourseDescriptionDeleted'));
	}
	// Add or edit a description block
	else
	{
		$sql = "SELECT * FROM $tbl_course_description WHERE id='$description_id'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if ($description = mysql_fetch_array($result))
		{
			$default_description_titles[$description_id] = $description['title'];
			$description_content = $description['content'];
		}
		// Build the form
		$form = new FormValidator('course_description');
		$form->addElement('hidden', 'description_id');
		if (($description_id == ADD_BLOCK) || $default_description_title_editable[$description_id])
		{
			$form->add_textfield('title', get_lang('Title'));
		}
		$form->add_html_editor('content', get_lang('Content'));
		$form->addElement('submit', null, get_lang('Ok'));
		// Set some default values
		$default['title'] = $default_description_titles[$description_id];
		$default['content'] = $description_content;
		$default['description_id'] = $description_id;
		$form->setDefaults($default);
		// If form validates: save the description block
		if ($form->validate())
		{
			$description = $form->exportValues();
			$content = $description['content'];
			$title = $description['title'];
			if ($description['description_id'] == ADD_BLOCK)
			{
				$sql = "SELECT MAX(id) FROM $tbl_course_description";
				$result = api_sql_query($sql, __FILE__, __LINE__);
				list ($new_id) = mysql_fetch_row($result);
				$new_id = max(sizeof($default_description_titles), $new_id);
				$sql = "INSERT IGNORE INTO $tbl_course_description SET id = '".$new_id."', title = '".mysql_real_escape_string($title)."', content = '".mysql_real_escape_string($content)."'";
				api_sql_query($sql, __FILE__, __LINE__);
			}
			else
			{
				if (!$default_description_title_editable[$description_id])
				{
					$title = $default_description_titles[$description_id];
				}
				$sql = "DELETE FROM $tbl_course_description WHERE id = '".$description_id."'";
				api_sql_query($sql, __FILE__, __LINE__);
				$sql = "INSERT IGNORE INTO $tbl_course_description SET id = '".$description_id."', title = '".mysql_real_escape_string($title)."', content = '".mysql_real_escape_string($content)."'";
				api_sql_query($sql, __FILE__, __LINE__);
			}
			Display :: display_normal_message(get_lang('CourseDescriptionUpdated'));
		}
		// Show the form
		else
		{
			if ($show_peda_suggest)
			{
				echo '<dl>';
				if (isset ($question[$description_id]))
				{
					echo '<dt><b>'.get_lang('QuestionPlan').'</b></dt>';
					echo '<dd>'.$question[$description_id].'</dd>';
				}
				if (isset ($information[$description_id]))
				{
					echo '<dt><b>'.get_lang('Info2Say').'</b></dt>';
					echo '<dd>'.$information[$description_id].'</dd>';
				}
				echo '</dl>';
			}
			$form->display();
			$show_description_list = false;
		}
	}
}
// Show the list of all description blocks
if ($show_description_list)
{
	$sql = "SELECT * FROM $tbl_course_description ORDER BY id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$descriptions;
	while ($description = mysql_fetch_object($result))
	{
		$descriptions[$description->id] = $description;
	}
	if (api_is_allowed_to_edit())
	{
		$categories = array ();
		foreach ($default_description_titles as $id => $title)
		{
			if (!array_key_exists($id, $descriptions))
			{
				$categories[$id] = $title;
			}
		}
		$categories[ADD_BLOCK] = get_lang('NewBloc');

		$cat_form = new FormValidator('category', 'get');
		$group = array ();
		$group[] = $cat_form->createElement('select', 'description_id', get_lang('AddCat'), $categories);
		$group[] = $cat_form->createElement('submit', null, get_lang('Ok'));
		$cat_form->addGroup($group, 'cat', get_lang('AddCat'), null, false);
		$cat_form->display();
	}
	if (count($descriptions) > 0)
	{
		foreach ($descriptions as $id => $description)
		{
			echo '<hr noshade="noshade" size="1"/>';
			echo '<div>';
			if (api_is_allowed_to_edit())
			{
				echo '<a href="'.$_SERVER['PHP_SELF'].'?description_id='.$description->id.'">';
				echo '<img src="../img/edit.gif" alt="'.get_lang('Modify').'" border="0"  style="vertical-align:middle;float:right;margin:2px;" />';
				echo '</a> ';
				echo '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang('ConfirmYourChoice'))).'\')) return false;">';
				echo '<img src="../img/delete.gif" alt="'.get_lang("Delete").'" border="0" style="vertical-align:middle;float:right;margin:2px;" />';
				echo '</a> ';
			}
			echo '<h3>'.$description->title.'</h3>';
			echo '</div>';
			echo text_filter($description->content);
		}
	}
	else
	{
		echo '<br /><em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
	}
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>