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
	echo '<div class="actions">';
	echo '<a href="index.php?'.api_get_cidreq().$param_gradebook.'&action=attendance_add">'.Display::return_icon('new_attendance_list.png',get_lang('CreateANewAttendance'),'',ICON_SIZE_MEDIUM).'</a>';	
	echo '</div>';
}

/*

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
}*/



//jqgrid will use this URL to do the selects
//$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_careers';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('Description'), get_lang('CountDoneAttendance'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',        'width'=>'300',   'align'=>'left'),
                        array('name'=>'description',    'index'=>'description', 'width'=>'200',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'attendance_qualify_max',    'index'=>'attendance_qualify_max', 'width'=>'80',  'align'=>'left','sortable'=>'false')                        
                       );

if (api_is_allowed_to_edit(null, true)) {
    $columns[] = get_lang('Actions');
    $column_model[] = array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left','sortable'=>'false');
}

$extra_params = array();
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 

$data = Attendance::get_attendance_data();

?>
<script>
$(function() {
<?php 
    // grid definition see the $career->display() function
    echo Display::grid_js('attendance',  'false', $columns, $column_model, $extra_params, $data, null, true);       
?> 
});
</script>
<?php

echo Display::grid_html('attendance');  