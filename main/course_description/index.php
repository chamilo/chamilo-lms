<?php // $Id: index.php 15189 2008-04-30 14:58:34Z elixir_inter $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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
$language_file = array ('course_description', 'pedaSuggest', 'accessibility');

include ('../inc/global.inc.php');
$this_section = SECTION_COURSES;

include (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

include_once(api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php');
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

//$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('CourseProgram'));

if(isset($_GET['description_id']) && $_GET['description_id']==1) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('GeneralDescription'));
if(isset($_GET['description_id']) && $_GET['description_id']==2) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Objectives'));
if(isset($_GET['description_id']) && $_GET['description_id']==3) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Topics'));
if(isset($_GET['description_id']) && $_GET['description_id']==4) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Methodology'));
if(isset($_GET['description_id']) && $_GET['description_id']==5) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('CourseMaterial'));
if(isset($_GET['description_id']) && $_GET['description_id']==6) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('HumanAndTechnicalResources'));
if(isset($_GET['description_id']) && $_GET['description_id']==7) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Assessment'));
if(isset($_GET['description_id']) && $_GET['description_id']==8) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('NewBloc'));

api_protect_course_script();
$nameTools = get_lang('CourseProgram');
Display :: display_header($nameTools, 'Description');
//api_display_tool_title($nameTools);



/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$nameTools = get_lang(TOOL_COURSE_DESCRIPTION);

$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
$show_description_list = true;
$show_peda_suggest = true;
define('ADD_BLOCK', 8);
// Default descriptions
$default_description_titles = array();
$default_description_titles[1]= get_lang('GeneralDescription');
$default_description_titles[2]= get_lang('Objectives');
$default_description_titles[3]= get_lang('Topics');
$default_description_titles[4]= get_lang('Methodology');
$default_description_titles[5]= get_lang('CourseMaterial');
$default_description_titles[6]= get_lang('HumanAndTechnicalResources');
$default_description_titles[7]= get_lang('Assessment');
$default_description_icon = array();
$default_description_icon[1]= api_get_path(WEB_IMG_PATH).'/edu_miscellaneous.gif';
$default_description_icon[2]= api_get_path(WEB_IMG_PATH).'/spire.gif';
$default_description_icon[3]= api_get_path(WEB_IMG_PATH).'/kcmdf_big.gif';
$default_description_icon[4]= api_get_path(WEB_IMG_PATH).'/misc.gif';
$default_description_icon[5]= api_get_path(WEB_IMG_PATH).'/laptop.gif';
$default_description_icon[6]= api_get_path(WEB_IMG_PATH).'/personal.gif';
$default_description_icon[7]= api_get_path(WEB_IMG_PATH).'/korganizer.gif';
$default_description_icon[8]= api_get_path(WEB_IMG_PATH).'/ktip.gif';
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
		Display :: display_confirmation_message(get_lang('CourseDescriptionDeleted'));
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
		
		$fck_attribute['Width'] = '100%';
		$fck_attribute['Height'] = '225';
		$fck_attribute['ToolbarSet'] = 'Middle';
		
		echo '
		<style>
		.row{
			width:100%;
		}
		div.row div.label {
			width: 60px;
		}
		
		div.row div.formw {
			width: 100%;
		}
		</style>';
		
		// Build the form
		$form = new FormValidator('course_description','POST','index.php','','style="width: 100%;"');
		$form->addElement('hidden', 'description_id');
		if (($description_id == ADD_BLOCK) || $default_description_title_editable[$description_id])
		{
			$form->add_textfield('title', get_lang('Title'), true, array('style'=>'width: 350px;'));
		}
		
		if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
			WCAG_rendering::prepare_admin_form($description_content, $form);
		} else {
			$form->add_html_editor('contentDescription', get_lang('Content'));
		}
		$form->addElement('submit', null, get_lang('Ok'));
		// Set some default values
		$default['title'] = $default_description_titles[$description_id];
		$default['contentDescription'] = $description_content;
		$default['description_id'] = $description_id;
		if($description_id == ADD_BLOCK) $default['description_id'] = ADD_BLOCK;
		$form->setDefaults($default);
		// If form validates: save the description block
		if ($form->validate())
		{
			$description = $form->exportValues();
			if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
				$content = WCAG_Rendering::prepareXHTML();
			} else {
				$content = $description['contentDescription'];
			}
			$title = $description['title'];
			if ($description['description_id'] == ADD_BLOCK)
			{
				$sql = "SELECT MAX(id) FROM $tbl_course_description";
				$result = api_sql_query($sql, __FILE__, __LINE__);
				$sql = "INSERT IGNORE INTO $tbl_course_description SET id = '".$description_id."', title = '".mysql_real_escape_string($title)."', content = '".mysql_real_escape_string($content)."'";
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
			Display :: display_confirmation_message(get_lang('CourseDescriptionUpdated'));
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
					//echo '<dt><b>'.get_lang('Info2Say').'</b></dt>';
					//echo '<dd>'.$information[$description_id].'</dd>';
				}
				echo '</dl>';
			}
			if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
				echo (WCAG_Rendering::editor_header());
			}
			$form->display();
			if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
				echo (WCAG_Rendering::editor_footer());
			}
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
		echo '<div style="position: relative;width: 500px;">';
		Display::display_normal_message(get_lang('CourseDescriptionIntro'),false);
		echo "</div>";
		$categories = array ();
		
		foreach ($default_description_titles as $id => $title)
		{
			$categories[$id] = $title;
		}
		$categories[ADD_BLOCK] = get_lang('NewBloc');
		
		$i=1;
		foreach ($categories as $id => $title){
			if($i==1 || $i==5){
				echo '<div style="padding-bottom: 5px;margin-bottom: 0px;">';
			}
			echo '<div style="float: left;width:150px; text-align:center; margin-right: 5px;">
	            	<a href="'.api_get_self().'?'.api_get_cidreq().'&description_id='.$id.'"><img src="'.$default_description_icon[$id].'" /><br>'.$title.'</a>
	        	</div>';
        	if($i==4 || $i==8){
				echo '<div style="clear: both"></div></div>';
			}
			$i++;
		}
		
		echo '<br>';
	}
	if (isset($descriptions) && count($descriptions) > 0)
	{
		foreach ($descriptions as $id => $description)
		{
			echo '<hr noshade="noshade" size="1"/>';
			echo '<div>';
			if (api_is_allowed_to_edit())
			{
				
				//delete
				echo '<a href="'.api_get_self().'?action=delete&amp;description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;">';
				echo '<img src="../img/delete.gif" alt="'.get_lang("Delete").'" border="0" style="vertical-align:middle;float:right;margin:2px;" />';
				echo '</a> ';
				
				
				//edit
				echo '<a href="'.api_get_self().'?action=edit&amp;description_id='.$description->id.'">';
				echo '<img src="../img/edit.gif" alt="'.get_lang("Edit").'" border="0" style="vertical-align:middle;float:right;margin:2px;" />';
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