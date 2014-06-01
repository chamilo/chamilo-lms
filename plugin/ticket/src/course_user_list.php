<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */
/**
 * Init section
 */
$language_file = array('registration');
require_once '../config.php';
$plugin = TicketPlugin::create();

$userId = intval($_GET['user_id']);
$userInfo = api_get_user_info($userId);

$coursesList = CourseManager::get_courses_list_by_user_id($userId, false, true);
$arrCourseList = array(get_lang('Select'));
//Course List
foreach ($coursesList as $key => $course) {
    $courseInfo = CourseManager::get_course_information($course['code']);
    $arrCourseList[$courseInfo['code']] = $courseInfo['title'];
}
//End Course List


$userLabel = Display::tag('label', get_lang('User'), array('class' => 'control-label'));
$personName = api_get_person_name($userInfo['firstname'], $userInfo['lastname']);
$userInput = Display::tag(
    'input',
    '', 
    array(
        'disabled' => 'disabled',
        'type' => 'text',
        'value' => $personName
    )
);
$userControl = Display::div($userInput, array('class' => 'controls'));

$courseLabel = Display::tag('label', get_lang('Course'), array('class' => 'control-label'));
$courseSelect = Display::select('course_id', $arrCourseList, 0, array(), false);
$courseControl = Display::div($courseSelect, array('class' => 'controls'));

$userDiv = Display::div($userLabel . " " . $userControl, array('class' => 'control-group'));
$courseDiv = Display::div($courseLabel . " " . $courseControl, array('class' => 'control-group'));
echo $userDiv;
echo $courseDiv;

