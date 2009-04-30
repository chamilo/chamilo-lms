<?php // $Id: index.php 20239 2009-04-30 22:44:21Z cfasanando $
/* For licensing terms, see /dokeos_license.txt */
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

include '../inc/global.inc.php';
$this_section = SECTION_COURSES;

include api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

include_once api_get_path(LIBRARY_PATH).'WCAG/WCAG_rendering.php';
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('CourseProgram'));

$description_id = isset ($_REQUEST['description_id']) ? Security::remove_XSS($_REQUEST['description_id']) : null;
$action = isset($_GET['action'])?Security::remove_XSS($_GET['action']):'';
$edit = isset($_POST['edit'])?Security::remove_XSS($_POST['edit']):'';
$add = isset($_POST['add'])?Security::remove_XSS($_POST['add']):'';

if(intval($description_id) == 1) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('GeneralDescription'));
if(intval($description_id) == 2) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Objectives'));
if(intval($description_id) == 3) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Topics'));
if(intval($description_id) == 4) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Methodology'));
if(intval($description_id) == 5) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('CourseMaterial'));
if(intval($description_id) == 6) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('HumanAndTechnicalResources'));
if(intval($description_id) == 7) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('Assessment'));
if(intval($description_id) == 8) $interbreadcrumb[] = array ("url" => "#", "name" => get_lang('NewBloc'));

api_protect_course_script(true);
$nameTools = get_lang('CourseProgram');
Display :: display_header('');
//api_display_tool_title($nameTools);



/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$nameTools = get_lang(TOOL_COURSE_DESCRIPTION);


/*
-----------------------------------------------------------
	Introduction section
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_COURSE_DESCRIPTION);
$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.


// These settings are for the other instances of the online editor.
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'CourseDescription';

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
$default_description_icon[1]= 'edu_miscellaneous.gif';
$default_description_icon[2]= 'spire.gif';
$default_description_icon[3]= 'kcmdf_big.gif';
$default_description_icon[4]= 'misc.gif';
$default_description_icon[5]= 'laptop.gif';
$default_description_icon[6]= 'personal.gif';
$default_description_icon[7]= 'korganizer.gif';
$default_description_icon[8]= 'ktip.gif';
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

if (api_is_allowed_to_edit() && !is_null($description_id) || $action =='add') {	
	$description_id = intval($description_id);
	// Delete a description block
	if ($action == 'delete') {
		$sql = "DELETE FROM $tbl_course_description WHERE id='".$description_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
		Display :: display_confirmation_message(get_lang('CourseDescriptionDeleted'));
	}
	// Add or edit a description block
	else {
		if (!empty($description_id)) {
		$sql = "SELECT * FROM $tbl_course_description WHERE id='".$description_id."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
			if ($description = Database::fetch_array($result)) {
				$default_description_titles[$description_id] = $description['title'];
				$description_content = $description['content'];
	
			} else {
				$current_title = $default_description_titles[$description_id];				
			}	

		} else {
			$sql = "SELECT MAX(id) as MAX FROM $tbl_course_description ";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$max= Database::fetch_array($result);				
			$description_id = $max['MAX']+1;
			if ($description_id < ADD_BLOCK) {
					$description_id=8;
			} 			
		}
		//Se borro: echo ' <style> .row{} <\style> por que hacia conflicto en apartado personalizado con los estilos propios del formvalidator 		
		// Build the form
		$form = new FormValidator('course_description','POST','index.php','','style="width: 100%;"');
		$form->addElement('header', '', $default_description_titles[$description_id]);
		$form->addElement('hidden', 'description_id');
		
		if ($action == 'edit' || intval($edit) == 1 ) {
			$form->addElement('hidden', 'edit','1');
		}		

		if ($action == 'add' || intval($add) == 1 ) {
			$form->addElement('hidden', 'add','1');	
		}		
			
		if (($description_id >= ADD_BLOCK) || $default_description_title_editable[$description_id] || $action == 'add' || intval($edit) == 1) {			
			$form->add_textfield('title', get_lang('Title'), true, array('size'=>'width: 350px;'));
			$form->applyFilter('title','html_filter');
		}
		
		if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
			WCAG_rendering::prepare_admin_form($description_content, $form);
		} else {			
			$form->add_html_editor('contentDescription', get_lang('Content'));			
		}
		$form->addElement('style_submit_button', null, get_lang('SaveIntroText'), 'class="save"');
		// Set some default values
		$default['title'] = $default_description_titles[$description_id];
		$default['contentDescription'] = $description_content;
		$default['description_id'] = $description_id;
		if ($description_id == ADD_BLOCK) {
			$default['description_id'] = ADD_BLOCK;
		} 
		$form->setDefaults($default);
		// If form validates: save the description block
		if ($form->validate()) {
			$description = $form->exportValues();
			if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
				$content = WCAG_Rendering::prepareXHTML();
			} else {
				$content = $description['contentDescription'];
			}
			$title = $description['title'];
			if ($description['description_id'] >= ADD_BLOCK) {
				if ($description['edit']=='1') {					
					$sql = "UPDATE $tbl_course_description SET  title = '".Database::escape_string($title)."', content = '".Database::escape_string($content)."' WHERE id = '".$description_id."' ";				
					api_sql_query($sql, __FILE__, __LINE__);					
				} else {								
					$result = api_sql_query($sql, __FILE__, __LINE__);
					$sql = "INSERT IGNORE INTO $tbl_course_description SET id = '".$description_id."', title = '".Database::escape_string($title)."', content = '".Database::escape_string($content)."'";				
					api_sql_query($sql, __FILE__, __LINE__);
				}
				/*$sql = "SELECT id FROM $tbl_course_description WHERE id = ".ADD_BLOCK;
				$result = api_sql_query($sql, __FILE__, __LINE__);
                if (Database::num_rows($result)>0){
                	$sqldel = "DELETE FROM $tbl_course_description WHERE id = ".ADD_BLOCK;
                    $resultdel = api_sql_query($sqldel,__FILE__,__LINE__);
                }
				$sqlins = "INSERT INTO $tbl_course_description SET id = '".$description_id."', title = '".Database::escape_string($title)."', content = '".Database::escape_string($content)."'";
				api_sql_query($sqlins, __FILE__, __LINE__);*/
			} else {
				if (!$default_description_title_editable[$description_id]) {
					$title = $default_description_titles[$description_id];
				}
				$sql = "DELETE FROM $tbl_course_description WHERE id = '".$description_id."'";
				api_sql_query($sql, __FILE__, __LINE__);
				$sql = "INSERT IGNORE INTO $tbl_course_description SET id = '".$description_id."', title = '".Database::escape_string($title)."', content = '".Database::escape_string($content)."'";
				api_sql_query($sql, __FILE__, __LINE__);
			}
			Display :: display_confirmation_message(get_lang('CourseDescriptionUpdated'));
		}
		// Show the form
		else {
				// menu top 
				//***********************************
					if (api_is_allowed_to_edit()) {
						$categories = array ();
						
						foreach ($default_description_titles as $id => $title) {
							$categories[$id] = $title;
						}
						$categories[ADD_BLOCK] = get_lang('NewBloc');
						
						$i=1;
						echo '<div class="actions">';
						ksort($categories);
						foreach ($categories as $id => $title) {
							if ($i==8) { 
								echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add">'.Display::return_icon($default_description_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>';
								break;
							} else {
								echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&description_id='.$id.'">'.Display::return_icon($default_description_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>&nbsp;&nbsp;';
								$i++;
							}
						}
						echo '</div>';
					}
				//***********************************
			if ($show_peda_suggest) {
				if (isset ($question[$description_id])) {
					$message = '<strong>'.get_lang('QuestionPlan').'</strong><br />';
					$message .= $question[$description_id];
					Display::display_normal_message($message, false);
				}
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
if ($show_description_list) {
	$sql = "SELECT * FROM $tbl_course_description ORDER BY id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$descriptions;
	while ($description = Database::fetch_object($result)) {
		$descriptions[$description->id] = $description;
	}
	if (api_is_allowed_to_edit()) {
		$categories = array ();
		
		foreach ($default_description_titles as $id => $title) {
			$categories[$id] = $title;
		}
		$categories[ADD_BLOCK] = get_lang('NewBloc');
		
		$i=1;
		echo '<div class="actions" style="margin-bottom:30px">';
		ksort($categories);
		foreach ($categories as $id => $title) {
			if ($i==8) { 
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add">'.Display::return_icon($default_description_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>';
				break;
			} else {
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&description_id='.$id.'">'.Display::return_icon($default_description_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>&nbsp;&nbsp;';
				$i++;
			}
		}
		echo '</div>';
	}
	if (isset($descriptions) && count($descriptions) > 0) {
		foreach ($descriptions as $id => $description) {
			echo '<div class="sectiontitle">';
			if (api_is_allowed_to_edit()) {
				//delete
				echo '<a href="'.api_get_self().'?action=delete&amp;description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;">';
				echo Display::return_icon('delete.gif', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'));
				echo '</a> ';
				
				//edit
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;description_id='.$description->id.'">';
				echo Display::return_icon('edit.gif', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'));
				echo '</a> ';
			}
			echo $description->title;
			echo '</div>';
			echo '<div class="sectioncomment">';
			echo text_filter($description->content);
			echo '</div>';
		}
	} else {
		echo '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
	}
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>
