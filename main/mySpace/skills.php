<?php
/* For licensing terms, see /license.txt */
/**
 * Skills reporting
 * @package chamilo.reporting
 */
require_once '../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$interbreadcrumb[] = array("url" => "index.php", "name" => get_lang('MySpace'));

$toolName = get_lang('Skills');

$selectedCourse = isset($_REQUEST['course']) ? intval($_REQUEST['course']) : null;
$selectedSkill = isset($_REQUEST['skill']) ? intval($_REQUEST['skill']) : 0;

$action = null;

if (!empty($selectedCourse)) {
    $action = 'filterByCourse';
} else if (!empty($selectedSkill)) {
    $action = 'filterBySkill';
}

$userId = api_get_user_id();

$courses = CourseManager::getCoursesFollowedByUser(
        $userId, DRH, null, null, null, null, false
);

$tableRows = array();
$reportTitle = null;

$objSkill = new Skill();
$skills = $objSkill->get_all();

switch ($action) {
    case 'filterByCourse':
        $course = api_get_course_info_by_id($selectedCourse);

        $reportTitle = sprintf(get_lang('AchievedSkillByCourseX'), $course['name']);

        $tableRows = $objSkill->listAchievedByCourse($selectedCourse);
        break;
    case 'filterBySkill':
        $skill = $objSkill->get($selectedSkill);

        $reportTitle = sprintf(get_lang('StudentsWhoAchievedTheSkillX'), $skill['name']);

        $students = UserManager::getUsersFollowedByUser($userId, STUDENT, false, false, false, null, null, null, null,
                null, null, DRH);

        foreach ($students as $student) {
            $tableRows = $objSkill->listUsersWhoAchieved($selectedSkill, $student['user_id']);
        }

        break;
}

foreach ($tableRows as &$row) {
    $row['completeName'] = api_get_person_name($row['firstname'], $row['lastname']);
    $row['achievedAt'] = api_format_date($row['acquired_skill_at'], DATE_FORMAT_NUMBER);

    if (file_exists(api_get_path(SYS_COURSE_PATH) . $row['c_directory'] . '/course-pic85x85.png')) {
        $row['courseImage'] = api_get_path(WEB_COURSE_PATH) . $row['c_directory'] . '/course-pic85x85.png';
    } else {
        $row['courseImage'] = Display::return_icon('course.png', null, null, ICON_SIZE_BIG, null, true);
    }
}

/*
 * View
 */
$tpl = new Template($toolName);

$tpl->assign('action', $action);

$tpl->assign('courses', $courses);
$tpl->assign('skills', $skills);

$tpl->assign('selectedCourse', $selectedCourse);
$tpl->assign('selectedSkill', $selectedSkill);

$tpl->assign('reportTitle', $reportTitle);
$tpl->assign('rows', $tableRows);

$contentTemplate = $tpl->get_template('my_space/skills.tpl');

$tpl->display($contentTemplate);
