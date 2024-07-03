<?php
/* For licensing terms, see /license.txt */

/**
 * View (MVC patter) for adding a attendance.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 *
 * @package chamilo.attendance
 */

// protect a course script
api_protect_course_script(true);

// error messages
if (isset($error)) {
    echo Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error', false);
}

if (!isset($error)) {
    $token = Security::get_token();
}
// display form
$form = new FormValidator(
    'attendance_add',
    'POST',
    'index.php?action=attendance_add&'.api_get_cidreq()
);
$form->addElement('header', '', get_lang('CreateANewAttendance'));
$form->addElement('hidden', 'sec_token', $token);

$form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->addHtmlEditor(
    'description',
    get_lang('Description'),
    false,
    false,
    ['ToolbarSet' => 'Basic', 'Width' => '100%', 'Height' => '150']
);

// Advanced Parameters
if ((api_get_session_id() != 0 && Gradebook::is_active()) || api_get_session_id() == 0) {
    $form->addButtonAdvancedSettings('id_qualify');

    $form->addElement('html', '<div id="id_qualify_options" style="display:none">');

    // Qualify Attendance for gradebook option
    $form->addElement(
        'checkbox',
        'attendance_qualify_gradebook',
        '',
        get_lang('QualifyAttendanceGradebook'),
        'onclick="javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"'
    );
    $form->addElement('html', '<div id="options_field" style="display:none">');

    GradebookUtils::load_gradebook_select_in_tool($form);

    $form->addElement('text', 'attendance_qualify_title', get_lang('TitleColumnGradebook'));
    $form->applyFilter('attendance_qualify_title', 'html_filter');
    $form->addElement(
        'text',
        'attendance_weight',
        get_lang('QualifyWeight'),
        'value="0.00" Style="width:40px" onfocus="javascript: this.select();"'
    );
    $form->applyFilter('attendance_weight', 'html_filter');
    $form->addElement('html', '</div>');
    Skill::addSkillsToForm($form, api_get_course_int_id(), api_get_session_id(), ITEM_TYPE_ATTENDANCE, 0);
    $form->addElement('html', '</div>');
}
$form->addButtonCreate(get_lang('Save'));
$form->display();
