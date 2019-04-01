<?php
/* For licensing terms, see /license.txt */

exit;

/**
 * @package chamilo.plugin.ticket
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = (int) $_GET['user_id'];
$userInfo = api_get_user_info($userId);

$coursesList = CourseManager::get_courses_list_by_user_id($userId, false, true);
$arrCourseList = [get_lang('Select')];
//Course List
foreach ($coursesList as $key => $course) {
    $courseInfo = CourseManager::get_course_information($course['code']);
    $arrCourseList[$courseInfo['code']] = $courseInfo['title'];
}

$userLabel = Display::tag('label', get_lang('User'), ['class' => 'control-label']);
$personName = api_get_person_name($userInfo['firstname'], $userInfo['lastname']);
$userInput = Display::tag(
    'input',
    '',
    [
        'disabled' => 'disabled',
        'type' => 'text',
        'value' => $personName,
    ]
);
$userControl = Display::div($userInput, ['class' => 'controls']);
$courseLabel = Display::tag('label', get_lang('Course'), ['class' => 'control-label']);
$courseSelect = Display::select('course_id', $arrCourseList, 0, [], false);
$courseControl = Display::div($courseSelect, ['class' => 'controls']);

$userDiv = Display::div($userLabel." ".$userControl, ['class' => 'control-group']);
$courseDiv = Display::div($courseLabel." ".$courseControl, ['class' => 'control-group']);

echo $userDiv;
echo $courseDiv;
