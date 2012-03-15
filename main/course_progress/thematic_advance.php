<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for thematic advance 
* @author Christian Fasanando <christian1827@gmail.com>
* @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 Bug fixing
* @package chamilo.course_progress
*/

// protect a course script
api_protect_course_script(true);

if ($action == 'thematic_advance_add' || $action == 'thematic_advance_edit') {    

	$header_form = get_lang('NewThematicAdvance');
	if ($action == 'thematic_advance_edit') {
		$header_form = get_lang('EditThematicAdvance');
	}

	if (!$start_date_error && !$duration_error) {
		$token = md5(uniqid(rand(),TRUE));
		$_SESSION['thematic_advance_token'] = $token;
	}

	// display form
	$form = new FormValidator('thematic_advance','POST','index.php?action=thematic_advance_list&thematic_id='.$thematic_id.'&'.api_get_cidreq(),'','style="width: 100%;"');
	$form->addElement('header', '', $header_form);	
	$form->addElement('hidden', 'thematic_advance_token',$token);
	$form->addElement('hidden', 'action', $action);
	
	if (!empty($thematic_advance_id)) {
		$form->addElement('hidden', 'thematic_advance_id',$thematic_advance_id);
	}
	if (!empty($thematic_id)) {
		$form->addElement('hidden', 'thematic_id',$thematic_id);
	}
		
	$radios = array();
	$radios[] = FormValidator::createElement('radio', 'start_date_type', null, get_lang('StartDateFromAnAttendance'),'1',array('onclick' => 'check_per_attendance(this)', 'id'=>'from_attendance'));
	$radios[] = FormValidator::createElement('radio', 'start_date_type', null, get_lang('StartDateCustom'),'2',array('onclick' => 'check_per_custom_date(this)', 'id'=>'custom_date'));
	$form->addGroup($radios, null, get_lang('StartDateOptions'));

	if (isset($thematic_advance_data['attendance_id']) && $thematic_advance_data['attendance_id'] == 0) {
		$form->addElement('html', '<div id="div_custom_datetime" style="display:block">');				
	} else { 
		$form->addElement('html', '<div id="div_custom_datetime" style="display:none">');
	}	
	
	$form->addElement('datepicker', 'custom_start_date', get_lang('StartDate'), array('form_name'=>'thematic_advance'));		
	$form->addElement('html', '</div>');	
	
	if (isset($thematic_advance_data['attendance_id']) && $thematic_advance_data['attendance_id'] == 0) {
		$form->addElement('html', '<div id="div_datetime_by_attendance" style="display:none">');	
	} else {
		$form->addElement('html', '<div id="div_datetime_by_attendance" style="display:block">');	
	}

	if (count($attendance_select) > 1) {		
		$form->addElement('select', 'attendance_select', get_lang('Attendances'), $attendance_select, array('id' => 'id_attendance_select', 'onchange' => 'datetime_by_attendance(this.value)'));
	} else {
		$form->addElement('label', get_lang('Attendances'), '<strong><em>'.get_lang('ThereAreNoAttendancesInsideCourse').'</em></strong>');
	}
	
	$form->addElement('html', '<div id="div_datetime_attendance">');
	if (!empty($calendar_select)) {
		$form->addElement('select', 'start_date_by_attendance', get_lang('StartDate'), $calendar_select, array('id'=>'start_date_select_calendar'));
	}
	$form->addElement('html', '</div>');
		
	$form->addElement('html', '</div>');

	$form->add_textfield('duration_in_hours', get_lang('DurationInHours'), false, array('size'=>'3','id'=>'duration_in_hours_element'));
	
	$form->add_html_editor('content', get_lang('Content'), false, false, array('ToolbarStartExpanded'=>'false', 'ToolbarSet' => 'TrainingDescription', 'Width' => '80%', 'Height' => '150'));	
	//$form->addElement('textarea', 'content', get_lang('Content'));
		
	if ($action == 'thematic_advance_add') {
		$form->addElement('style_submit_button', null, get_lang('Save'), 'id="add_button" class="save"');
	} else {
		$form->addElement('style_submit_button', null, get_lang('Save'), 'id="update_button" class="save"');
	}
	//$form->addElement('html', '<a href="#" id="save_button" onclick="save();">Save</a>');
	$attendance_select_item_id = null;
	if (count($attendance_select) > 1) {
		$i = 1;
		foreach($attendance_select as $key => $attendance_select_item) {
			if ($i == 2) {
				$attendance_select_item_id = $key;
				break;
			}
			$i++;
		}
		if (!empty($attendance_select_item_id)) {
			$default['attendance_select'] = $attendance_select_item_id;
			echo '<script> datetime_by_attendance("'.$attendance_select_item_id.'"); </script>';
			
		}
	}
	
	$default['start_date_type'] = 1;	
	$default['custom_start_date'] = date('d-F-Y H:i',api_strtotime(api_get_local_time()));
	
	if (!empty($thematic_advance_data)) {

		// set default values
		$default['content'] = $thematic_advance_data['content'];
		$default['duration_in_hours'] = $thematic_advance_data['duration'];
		if (empty($thematic_advance_data['attendance_id'])) {
			$default['start_date_type'] = 2;
			$default['custom_start_date'] = date('d-F-Y H:i', api_strtotime(api_get_local_time($thematic_advance_data['start_date'])));
		} else {
			$default['start_date_type'] = 1;			
			if (!empty($thematic_advance_data['start_date'])) {
		        $default['start_date_by_attendance'] = api_get_local_time($thematic_advance_data['start_date']);
			}						

			$default['attendance_select'] = $thematic_advance_data['attendance_id'];
		}		
	}
	$form->setDefaults($default);
	
	// error messages
	$msg_error = '';
	if ($start_date_error) {	
		$msg_error .= get_lang('YouMustSelectAtleastAStartDate').'<br />';		
	}
	if ($duration_error) {	
		$msg_error .= get_lang('DurationInHoursMustBeNumeric');		
	}
	
	if (!empty($msg_error)) {
		Display::display_error_message($msg_error,false);	
	}
	$form->display();
	
} else if ($action == 'thematic_advance_list') {
	// thematic advance list		
	$table = new SortableTable('thematic_advance_list', array('Thematic', 'get_number_of_thematic_advances'), array('Thematic', 'get_thematic_advance_data'));
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false, array('style'=>'width:20px;'));
	$table->set_header(1, get_lang('StartDate'), false );
	$table->set_header(2, get_lang('DurationInHours'), false, array('style'=>'width:80px;'));
	$table->set_header(3, get_lang('Content'), false);
	
	if (api_is_allowed_to_edit(null, true)) {
		$table->set_header(4, get_lang('Actions'), false,array('style'=>'text-align:center'));	
	}	
	$table->display();	
}