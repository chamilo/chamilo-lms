<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for editing an attendance
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

$attendance_weight = floatval($attendance_weight);
// display form
$form = new FormValidator('attendance_edit','POST','index.php?action=attendance_edit&'.api_get_cidreq().'&attendance_id='.$attendance_id.$param_gradebook,'','style="width: 100%;"');
$form->addElement('header', '', get_lang('Edit'));
$form->addElement('hidden', 'sec_token',$token);
$form->addElement('hidden', 'attendance_id', $attendance_id);

$form->add_textfield('title', get_lang('Title'), true, array('size'=>'50'));
$form->applyFilter('title','html_filter');
$form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'TrainingDescription', 'Width' => '100%', 'Height' => '200'));

// Adavanced Parameters
$form->addElement('html', '<div class="row"><div class="label"></div>');
if (!empty($attendance_qualify_title) || !empty($attendance_weight)) {
	$form->addElement('html', '<div class="formw"><br /><a href="javascript://" class="advanced_parameters"><span id="img_plus_and_minus">&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).' '.get_lang('AdvancedParameters').'</span></a></div></div>');
	$form->addElement('html','<div id="id_qualify" style="display:block">');
	$form->addElement('checkbox', 'attendance_qualify_gradebook', '', get_lang('QualifyAttendanceGradebook'),array('checked'=>'true','onclick'=>'javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}'));
	$form -> addElement('html','<div id="options_field" style="display:block">');
} else {
	$form->addElement('html', '<div class="formw"><br /><a href="javascript://" class="advanced_parameters"><span id="img_plus_and_minus">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).' '.get_lang('AdvancedParameters').'</span></a></div></div>');
	$form->addElement('html','<div id="id_qualify" style="display:none">');
	$form->addElement('checkbox', 'attendance_qualify_gradebook', '', get_lang('QualifyAttendanceGradebook'),'onclick="javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');
	$form -> addElement('html','<div id="options_field" style="display:none">');
}
$form->addElement('text', 'attendance_qualify_title', get_lang('TitleColumnGradebook'));
$form->applyFilter('attendance_qualify_title', 'html_filter');
$form->addElement('text', 'attendance_weight', get_lang('QualifyWeight'),'value="0.00" Style="width:40px" onfocus="javascript: this.select();"');
$form->applyFilter('attendance_weight', 'html_filter');
$form->addElement('html','</div>');

$form->addElement('html','</div>');
$form->addElement('html','<div class="clear" style="margin-top:50px;"></div>');
$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');

// set default values
$default['title'] = Security::remove_XSS($title);
$default['description'] = Security::remove_XSS($description,STUDENT);
$default['attendance_qualify_title'] = $attendance_qualify_title;
$default['attendance_weight'] = $attendance_weight;
$form->setDefaults($default);
$form->display();
?>