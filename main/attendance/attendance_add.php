<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for adding a attendance
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.attendance
*/

// protect a course script
api_protect_course_script(true);

// error messages
if ($error) {
	Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'),false);
}

$param_gradebook = '';
if (isset($_SESSION['gradebook'])) {
	$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
}

if (!$error) {
	$token = Security::get_token();
}
// display form
$form = new FormValidator('attendance_add','POST','index.php?action=attendance_add&'.api_get_cidreq().$param_gradebook,'','style="width: 100%;"');
$form->addElement('header', '', get_lang('CreateANewAttendance'));
$form->addElement('hidden', 'sec_token', $token);

$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
$form->applyFilter('title','html_filter');
$form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '150'));

// Adavanced Parameters
$form->addElement('html', '<div class="row"><div class="label"></div>');
$form->addElement('html', '<div class="formw"><br /><a href="javascript://" class = "advanced_parameters" ><span id="img_plus_and_minus">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).' '.get_lang('AdvancedParameters').'</span></a></div></div>');
$form->addElement('html','<div id="id_qualify" style="display:none">');

// Qualify Attendance for gradebook option
$form->addElement('checkbox', 'attendance_qualify_gradebook', '', get_lang('QualifyAttendanceGradebook'),'onclick="javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');
$form -> addElement('html','<div id="options_field" style="display:none">');
$form->addElement('text', 'attendance_qualify_title', get_lang('TitleColumnGradebook'));
$form->applyFilter('attendance_qualify_title', 'html_filter');
$form->addElement('text', 'attendance_weight', get_lang('QualifyWeight'),'value="0.00" Style="width:40px" onfocus="javascript: this.select();"');
$form->applyFilter('attendance_weight', 'html_filter');
$form->addElement('html','</div>');

$form->addElement('html','</div>');
$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
$form->display();