<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.admin
 */

/* INITIALIZATION SECTION */

// Language files that need to be included.
$language_file = array('admin', 'create_course');

$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$tool_name = get_lang('AddCourse');
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'course_list.php', 'name' => get_lang('CourseList'));

/* MAIN CODE */

global $_configuration;

// Get all possible teachers.
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
$sql = "SELECT user_id,lastname,firstname FROM $table_user WHERE status=1".$order_clause;
// Filtering teachers when creating a course.
if ($_configuration['multiple_access_urls']) {
    $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $sql = "SELECT u.user_id,lastname,firstname FROM $table_user as u
            INNER JOIN $access_url_rel_user_table url_rel_user
            ON (u.user_id=url_rel_user.user_id) WHERE url_rel_user.access_url_id=".api_get_current_access_url_id()." AND status=1".$order_clause;
}

$res = Database::query($sql);
$teachers = array();
//$teachers[0] = '-- '.get_lang('NoManager').' --';
while($obj = Database::fetch_object($res)) {
    $teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

// Build the form.
$form = new FormValidator('update_course');
$form->addElement('header', '', $tool_name);

// Title
$form->add_textfield('title', get_lang('Title'), true, array ('class' => 'span6'));
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$form->add_textfield('visual_code', array(get_lang('CourseCode'), get_lang('OnlyLettersAndNumbers')) , false, array('class' => 'span3', 'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE));

$form->applyFilter('visual_code', 'api_strtoupper');
$form->applyFilter('visual_code', 'html_filter');
$form->addRule('visual_code', get_lang('Max'), 'maxlength', CourseManager::MAX_COURSE_LENGTH_CODE);

//$form->addElement('select', 'tutor_id', get_lang('CourseTitular'), $teachers, array('style' => 'width:350px', 'class'=>'chzn-select', 'id'=>'tutor_id'));
//$form->applyFilter('tutor_id', 'html_filter');

$form->addElement('select', 'course_teachers', get_lang('CourseTeachers'), $teachers, ' id="course_teachers" class="chzn-select"  style="width:350px" multiple="multiple" ');
$form->applyFilter('course_teachers', 'html_filter');

$categories_select = $form->addElement('select', 'category_code', get_lang('CourseFaculty'), $categories, array('style' => 'width:350px', 'class'=>'chzn-select', 'id'=>'category_code'));
$categories_select->addOption('-','');
$form->applyFilter('category_code', 'html_filter');
//This function fills the category_code select ...
CourseManager::select_and_sort_categories($categories_select);

// Course department
$form->add_textfield('department_name', get_lang('CourseDepartment'), false, array ('size' => '60'));
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

// Department URL
$form->add_textfield('department_url', get_lang('CourseDepartmentURL'), false, array ('size' => '60'));
$form->applyFilter('department_url', 'html_filter');

$form->addElement('select_language', 'course_language', get_lang('CourseLanguage'));
$form->applyFilter('select_language', 'html_filter');

$form->addElement('checkbox', 'exemplary_content', '', get_lang('FillWithExemplaryContent'));

$group = array();
$group[]= $form->createElement('radio', 'visibility', get_lang('CourseAccess'), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);

$form->addGroup($group,'', get_lang('CourseAccess'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[]= $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group,'', get_lang('Subscription'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[]= $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group,'', get_lang('Unsubscription'), '<br />');

$form->addElement('text','disk_quota',array(get_lang('CourseQuota'), null, get_lang('MB')));
$form->addRule('disk_quota', get_lang('ThisFieldShouldBeNumeric'), 'numeric');

$obj = new GradeModel();
$obj->fill_grade_model_select_in_form($form);

$form->add_progress_bar();
$form->addElement('style_submit_button', 'submit', get_lang('CreateCourse'), 'class="add"');

// Set some default values.
$values['course_language']  = api_get_setting('platformLanguage');
$values['disk_quota']       = round(api_get_setting('default_document_quotum')/1024/1024, 1);

$default_course_visibility = api_get_setting('courses_default_creation_visibility');

if (isset($default_course_visibility)) {
    $values['visibility']       = api_get_setting('courses_default_creation_visibility');
} else {
    $values['visibility']       = COURSE_VISIBILITY_OPEN_PLATFORM;    
}
$values['subscribe']        = 1;
$values['unsubscribe']      = 0;
reset($teachers);
//$values['course_teachers'] = key($teachers);

$form->setDefaults($values);

// Validate the form
if ($form->validate()) {
    $course          = $form->exportValues();    
    //$tutor_name      = $teachers[$course['tutor_id']];
    $teacher_id      = $course['tutor_id'];
    $course_teachers = $course['course_teachers'];    
    
    $course['disk_quota'] = $course['disk_quota']*1024*1024;
    
    $course['exemplary_content']    = empty($course['exemplary_content']) ? false : true;
    $course['teachers']             = $course_teachers;
    //$course['tutor_name']           = $tutor_name;
    $course['user_id']              = $teacher_id;  
    $course['wanted_code']          = $course['visual_code'];
    
    $course['gradebook_model_id']   = isset($course['gradebook_model_id']) ? $course['gradebook_model_id'] : null;            
    
    $course_info = CourseManager::create_course($course);

    header('Location: course_list.php'.($course_info===false?'?action=show_msg&warn='.api_get_last_failure():''));
    exit;
}

// Display the form.
$content = $form->return_form();

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
