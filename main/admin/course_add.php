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
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
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
$teachers[0] = '-- '.get_lang('NoManager').' --';
while($obj = Database::fetch_object($res)) {
    $teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

$dbnamelength = strlen($_configuration['db_prefix']);
// Ensure the database prefix + database name do not get over 40 characters.
$maxlength = 40 - $dbnamelength;

// Build the form.
$form = new FormValidator('update_course');
$form->addElement('header', '', $tool_name);

// Title
$form->add_textfield('title', get_lang('Title'), true, array ('size' => '60'));
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$form->add_textfield('visual_code', get_lang('CourseCode'), false, array('size' => '20', 'maxlength' => 20));
$form->applyFilter('visual_code', 'api_strtoupper');
$form->applyFilter('visual_code', 'html_filter');
$form->addRule('visual_code', get_lang('Max'), 'maxlength', $maxlength);

$form->addElement('select', 'tutor_id', get_lang('CourseTitular'), $teachers, array('style' => 'width:350px'));
$form->applyFilter('tutor_id', 'html_filter');

$form->addElement('select', 'course_teachers', get_lang('CourseTeachers'), $teachers, 'style="width:350px" multiple=multiple size=5');
$form->applyFilter('course_teachers', 'html_filter');

$categories_select = $form->addElement('select', 'category_code', get_lang('CourseFaculty'), $categories, array('style' => 'width:350px'));
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

$form->addElement('checkbox', 'exemplary_content', get_lang('FillWithExemplaryContent'));

$form->addElement('radio', 'visibility', get_lang('CourseAccess'), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$form->addElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$form->addElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$form->addElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$form->addElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$form->addElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$form->addElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);

$form->add_textfield('disk_quota', get_lang('CourseQuota'));
$form->addRule('disk_quota', get_lang('ThisFieldShouldBeNumeric'), 'numeric');
$form->add_progress_bar();
$form->addElement('style_submit_button', 'submit', get_lang('CreateCourse'), 'class="add"');

// Set some default values.
$values['course_language'] = api_get_setting('platformLanguage');
$values['disk_quota'] = api_get_setting('default_document_quotum');
$values['visibility'] = COURSE_VISIBILITY_OPEN_PLATFORM;
$values['subscribe'] = 1;
$values['unsubscribe'] = 0;
reset($teachers);
$values['course_teachers'] = key($teachers);
$form->setDefaults($values);

// Validate the form.
if ($form->validate()) {
    $course = $form->exportValues();
    $code = $course['visual_code'];
    $tutor_name = $teachers[$course['tutor_id']];
    $teacher_id = $course['tutor_id'];
    $course_teachers = $course['course_teachers'];
    $test = false;

    // The course tutor has been selected in the teachers list so we must remove him to avoid double records in the database.
    foreach ($course_teachers as $key => $value){
        if ($value == $teacher_id) {
            unset($course_teachers[$key]);
            break;
        }
    }
    $title = $course['title'];
    $category = $course['category_code'];
    $department_name = $course['department_name'];
    $department_url = $course['department_url'];
    $course_language = $course['course_language'];
    $exemplary_content = empty($course['exemplary_content']) ? false : true;
    $disk_quota = $course['disk_quota'];
    if (stripos($department_url, 'http://') === false && stripos($department_url, 'https://') === false) {
        $department_url = 'http://'.$department_url;
    }
    if (trim($code) == '') {
        $code = generate_course_code(api_substr($title, 0, $maxlength));
    }
    $keys = define_course_keys($code, '', $_configuration['db_prefix']);
    if (count($keys)) {
        $current_course_code = $keys['currentCourseCode'];
        $current_course_id = $keys['currentCourseId'];
        $current_course_db_name = $keys['currentCourseDbName'];
        
        $current_course_repository = $keys['currentCourseRepository'];
        $expiration_date = time() + $firstExpirationDelay;
        prepare_course_repository($current_course_repository, $current_course_id);
        update_Db_course($current_course_db_name);
        $pictures_array = fill_course_repository($current_course_repository, $exemplary_content);
        fill_Db_course($current_course_db_name, $current_course_repository, $course_language, $pictures_array, $exemplary_content);
        register_course($current_course_id, $current_course_code, $current_course_repository, $current_course_db_name, $tutor_name, $category, $title, $course_language, $teacher_id, $expiration_date, $course_teachers);
        $sql = "UPDATE $table_course SET disk_quota = '".$disk_quota."', visibility = '".Database::escape_string($course['visibility'])."', subscribe = '".Database::escape_string($course['subscribe'])."', unsubscribe='".Database::escape_string($course['unsubscribe'])."' WHERE code = '".$current_course_id."'";
        Database::query($sql);
        header('Location: course_list.php');
        exit ();
    }
}

// The header.
Display::display_header($tool_name);

// Display the form.
$form->display();

/* FOOTER */

Display :: display_footer();
