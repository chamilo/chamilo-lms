<?php
/* For licensing terms, see /license.txt */
/**
 * Skills reporting
 * @package chamilo.reporting
 */
require_once '../inc/global.inc.php';

if (!api_is_student_boss()) {
    api_not_allowed();
}

$this_section = SECTION_TRACKING;

$interbreadcrumb[] = array("url" => "index.php", "name" => get_lang('MySpace'));

$toolName = get_lang('Skills');

$userId = api_get_user_id();
$selectedStudent = isset($_REQUEST['student']) ? intval($_REQUEST['student']) : 0;

$tableRows = array();

$followedStudents = UserManager::getUsersFollowedByStudentBoss($userId);

$skillTable = Database::get_main_table(TABLE_MAIN_SKILL);
$skillRelUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
$userTable = Database::get_main_table(TABLE_MAIN_USER);
$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

foreach ($followedStudents as &$student) {
    $student['completeName'] = api_get_person_name($student['firstname'], $student['lastname']);
}

if ($selectedStudent > 0) {
    $sql = "SELECT s.name, sru.acquired_skill_at, c.title c_name, c.directory c_directory "
        . "FROM $skillTable s "
        . "INNER JOIN $skillRelUserTable sru ON s.id = sru.skill_id "
        . "INNER JOIN $courseTable c ON sru.course_id = c.id "
        . "WHERE sru.user_id = $selectedStudent";

    $result = Database::query($sql);

    while ($resultData = Database::fetch_assoc($result)) {
        $row = array(
            'completeName' => $followedStudents[$selectedStudent]['completeName'],
            'achievedAt' => api_format_date($resultData['acquired_skill_at'], DATE_FORMAT_NUMBER)
        );

        if (file_exists(api_get_path(SYS_COURSE_PATH) . "{$resultData['c_directory']}/course-pic85x85.png")) {
            $row['courseImage'] = api_get_path(WEB_COURSE_PATH) . "{$resultData['c_directory']}/course-pic85x85.png";
        } else {
            $row['courseImage'] = Display::return_icon('course.png', null, null, ICON_SIZE_BIG, null, true);
        }

        $tableRows[] = array_merge($resultData, $row);
    }
}

/*
 * View
 */
$tpl = new Template($toolName);

$tpl->assign('followedStudents', $followedStudents);
$tpl->assign('selectedStudent', $selectedStudent);

$tpl->assign('rows', $tableRows);

$contentTemplate = $tpl->get_template('my_space/team_skills.tpl');

$tpl->display($contentTemplate);
