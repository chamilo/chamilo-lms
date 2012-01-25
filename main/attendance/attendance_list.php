<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for listing attendances 
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.attendance
*/

// protect a course script
api_protect_course_script(true);

if (api_is_allowed_to_edit(null, true)) {
	$param_gradebook = '';
	if (isset($_SESSION['gradebook'])) {
		$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
	}
	echo '<div class="actions" style="margin-bottom:30px">';
	echo '<a href="index.php?'.api_get_cidreq().$param_gradebook.'&action=attendance_add">'.Display::return_icon('new_attendance_list.png',get_lang('CreateANewAttendance'),'','32').'</a>';	
	echo '</div>';
}

$table = new SortableTable('attendance_list', array('Attendance', 'get_number_of_attendances'), array('Attendance', 'get_attendance_data'), $default_column);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false, array('style'=>'width:20px;'));
$table->set_header(1, get_lang('Name'), true );
$table->set_header(2, get_lang('Description'), true);
$table->set_header(3, get_lang('CountDoneAttendance'), true, array('style'=>'width:90px;'));

if (api_is_allowed_to_edit(null, true)) {
	$table->set_header(4, get_lang('Actions'), false,array('style'=>'text-align:center'));
	$table->set_form_actions(array ('attendance_delete_select' => get_lang('DeleteAllAttendances')));	
}

if ($table->get_total_number_of_items() > 0) {
	$table->display();
}