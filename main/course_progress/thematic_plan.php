<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for thematic plan 
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_progress
*/

// actions menu
$categories = array ();
foreach ($default_thematic_plan_title as $id => $title) {
	$categories[$id] = $title;
}
$categories[ADD_THEMATIC_PLAN] = get_lang('NewBloc');

$i=1;
echo '<div class="actions" style="margin-bottom:30px">';
ksort($categories);
foreach ($categories as $id => $title) {
	if ($i == ADD_THEMATIC_PLAN) {
		echo '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_add&thematic_id='.$thematic_id.'&description_type='.$next_description_type.'">'.Display::return_icon($default_thematic_plan_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>';
		break;
	} else {
		echo '<a href="index.php?action=thematic_plan_edit&'.api_get_cidreq().'&description_type='.$id.'&thematic_id='.$thematic_id.'">'.Display::return_icon($default_thematic_plan_icon[$id], $title, array('height'=>'22')).' '.$title.'</a>&nbsp;&nbsp;';
		$i++;
	}
}
echo '</div>';

if ($action == 'thematic_plan_list') {

	if (isset($thematic_plan_data) && count($thematic_plan_data) > 0) {
		foreach ($thematic_plan_data as $thematic_plan) {	
			echo '<div class="sectiontitle">';			
				//delete
				echo '<a href="'.api_get_self().'?cidReq='.api_get_course_id().'&thematic_id='.$thematic_plan['thematic_id'].'&action=thematic_plan_delete&description_type='.$thematic_plan['description_type'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;">';
				echo Display::return_icon('delete.gif', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'));
				echo '</a> ';								
				//edit
				echo '<a href="'.api_get_self().'?cidReq='.api_get_course_id().'&thematic_id='.$thematic_plan['thematic_id'].'&action=thematic_plan_edit&description_type='.$thematic_plan['description_type'].'">';
				echo Display::return_icon('edit.gif', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'));
				echo '</a> ';
				echo $thematic_plan['title'];	
			echo '</div>';
			echo '<div class="sectioncomment">';
			echo text_filter($thematic_plan['description']);
			echo '</div>';
		}
	} else {
		echo '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
	}
	
} else if ($action == 'thematic_plan_add' || $action == 'thematic_plan_edit') {

	if ($description_type >= ADD_THEMATIC_PLAN) {
		$header_form = get_lang('NewBloc');
	} else {
		$header_form = $default_thematic_plan_title[$description_type];		
	}	
	if (!$error) {
		$token = md5(uniqid(rand(),TRUE));
		$_SESSION['thematic_plan_token'] = $token;
	}

	// display form
	$form = new FormValidator('thematic_plan_add','POST','index.php?action=thematic_plan_list&thematic_id='.$thematic_id.'&'.api_get_cidreq().$param_gradebook,'','style="width: 100%;"');
	$form->addElement('header', '', $header_form);
	$form->addElement('hidden', 'action', $action);
	$form->addElement('hidden', 'thematic_plan_token', $token);
	
	if (!empty($thematic_id)) {
		$form->addElement('hidden', 'thematic_id', $thematic_id);	
	}
	if (!empty($description_type)) {
		$form->addElement('hidden', 'description_type', $description_type);	
	}

	$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
	$form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '200'));	
	$form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
	$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
	
	if ($description_type < ADD_THEMATIC_PLAN) {
		$default['title'] = $default_thematic_plan_title[$description_type];
	}
	if (!empty($thematic_plan_data)) {
		// set default values
		$default['title'] = $thematic_plan_data[0]['title'];
		$default['description'] = $thematic_plan_data[0]['description'];		
	}	
	$form->setDefaults($default);
	
	if (isset($default_thematic_plan_question[$description_type])) {
		$message = '<strong>'.get_lang('QuestionPlan').'</strong><br />';
		$message .= $default_thematic_plan_question[$description_type];
		Display::display_normal_message($message, false);
	}

	// error messages
	if ($error) { 	
		Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'),false);	
	}
	
	$form->display();		
}

?>