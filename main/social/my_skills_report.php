<?php
/* For licensing terms, see /license.txt */

/**
 * Show the skills report.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.social.skill
 */
require_once __DIR__.'/../inc/global.inc.php';

$userId = api_get_user_id();
Skill::isAllowed($userId);

$isStudent = api_is_student();
$isStudentBoss = api_is_student_boss();
$isDRH = api_is_drh();

if (!$isStudent && !$isStudentBoss && !$isDRH) {
    header('Location: '.api_get_path(WEB_CODE_PATH).'social/skills_wheel.php');
    exit;
}

$action = isset($_GET['a']) ? $_GET['a'] : '';
switch ($action) {
    case 'generate_custom_skill':
        $certificate = new Certificate(0, api_get_user_id(), false, false);
        $certificate->generatePdfFromCustomCertificate();
        break;
    case 'generate':
        $certificate = Certificate::generateUserSkills(api_get_user_id());
        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.api_get_self());
        exit;
        break;
}

$skillTable = Database::get_main_table(TABLE_MAIN_SKILL);
$skillRelUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
$tableRows = [];
$objSkill = new Skill();
$tpl = new Template(get_lang('Skills'));
$tplPath = null;

$tpl->assign('allow_skill_tool', api_get_setting('allow_skills_tool') === 'true');
$tpl->assign('allow_drh_skills_management', api_get_setting('allow_hr_skills_management') === 'true');

if ($isStudent) {
    $result = $objSkill->getUserSkillsTable($userId);
    $tableRows = $result['skills'];
    $tpl->assign('skill_table', $result['table']);
    $tplPath = 'skill/student_report.tpl';
} elseif ($isStudentBoss) {
    $tableRows = [];
    $followedStudents = UserManager::getUsersFollowedByStudentBoss($userId);

    $frmStudents = new FormValidator('students', 'get');
    $slcStudent = $frmStudents->addSelect(
        'student',
        get_lang('Student'),
        ['0' => get_lang('Select')]
    );
    $frmStudents->addButtonSearch(get_lang('Search'));

    foreach ($followedStudents as &$student) {
        $student['completeName'] = api_get_person_name($student['firstname'], $student['lastname']);

        $slcStudent->addOption($student['completeName'], $student['user_id']);
    }

    if ($frmStudents->validate()) {
        $selectedStudent = (int) $frmStudents->exportValue('student');

        $sql = "SELECT s.name, sru.acquired_skill_at, c.title, c.directory
                FROM $skillTable s
                INNER JOIN $skillRelUserTable sru
                ON s.id = sru.skill_id
                LEFT JOIN $courseTable c
                ON sru.course_id = c.id
                WHERE sru.user_id = $selectedStudent
                ";

        $result = Database::query($sql);

        while ($resultData = Database::fetch_assoc($result)) {
            $tableRow = [
                'complete_name' => $followedStudents[$selectedStudent]['completeName'],
                'skill_name' => Skill::translateName($resultData['name']),
                'achieved_at' => api_format_date($resultData['acquired_skill_at'], DATE_FORMAT_NUMBER),
                'course_image' => Display::return_icon(
                    'course.png',
                    null,
                    null,
                    ICON_SIZE_MEDIUM,
                    null,
                    true
                ),
                'course_name' => $resultData['title'],
            ];

            $imageSysPath = sprintf('%s%s/course-pic.png', api_get_path(SYS_COURSE_PATH), $resultData['directory']);

            if (file_exists($imageSysPath)) {
                $thumbSysPath = sprintf(
                    "%s%s/course-pic32.png",
                    api_get_path(SYS_COURSE_PATH),
                    $resultData['directory']
                );
                $thumbWebPath = sprintf(
                    "%s%s/course-pic32.png",
                    api_get_path(WEB_COURSE_PATH),
                    $resultData['directory']
                );

                if (!file_exists($thumbSysPath)) {
                    $courseImageThumb = new Image($imageSysPath);
                    $courseImageThumb->resize(32);
                    $courseImageThumb->send_image($thumbSysPath);
                }
                $tableRow['courseImage'] = $thumbWebPath;
            }
            $tableRows[] = $tableRow;
        }
    }

    $tplPath = 'skill/student_boss_report.tpl';
    $tpl->assign('form', $frmStudents->returnForm());
} elseif ($isDRH) {
    $selectedCourse = isset($_REQUEST['course']) ? intval($_REQUEST['course']) : null;
    $selectedSkill = isset($_REQUEST['skill']) ? intval($_REQUEST['skill']) : 0;
    $action = null;
    if (!empty($selectedCourse)) {
        $action = 'filterByCourse';
    } elseif (!empty($selectedSkill)) {
        $action = 'filterBySkill';
    }

    $courses = CourseManager::getCoursesFollowedByUser($userId, DRH);

    $tableRows = [];
    $reportTitle = null;
    $skills = $objSkill->get_all();

    switch ($action) {
        case 'filterByCourse':
            $course = api_get_course_info_by_id($selectedCourse);
            $reportTitle = sprintf(get_lang('AchievedSkillInCourseX'), $course['name']);
            $tableRows = $objSkill->listAchievedByCourse($selectedCourse);
            break;
        case 'filterBySkill':
            $skill = $objSkill->get($selectedSkill);
            $reportTitle = sprintf(get_lang('StudentsWhoAchievedTheSkillX'), $skill['name']);
            $students = UserManager::getUsersFollowedByUser(
                $userId,
                STUDENT,
                false,
                false,
                false,
                null,
                null,
                null,
                null,
                null,
                null,
                DRH
            );

            $coursesFilter = [];
            foreach ($courses as $course) {
                $coursesFilter[] = $course['id'];
            }

            $tableRows = $objSkill->listUsersWhoAchieved($selectedSkill, $coursesFilter);
            break;
    }

    foreach ($tableRows as &$row) {
        $row['complete_name'] = api_get_person_name($row['firstname'], $row['lastname']);
        $row['achieved_at'] = api_format_date($row['acquired_skill_at'], DATE_FORMAT_NUMBER);
        $row['course_image'] = Display::return_icon(
            'course.png',
            null,
            null,
            ICON_SIZE_MEDIUM,
            null,
            true
        );

        $imageSysPath = sprintf("%s%s/course-pic.png", api_get_path(SYS_COURSE_PATH), $row['c_directory']);

        if (file_exists($imageSysPath)) {
            $thumbSysPath = sprintf("%s%s/course-pic32.png", api_get_path(SYS_COURSE_PATH), $row['c_directory']);
            $thumbWebPath = sprintf("%s%s/course-pic32.png", api_get_path(WEB_COURSE_PATH), $row['c_directory']);

            if (!file_exists($thumbSysPath)) {
                $courseImageThumb = new Image($imageSysPath);
                $courseImageThumb->resize(32);
                $courseImageThumb->send_image($thumbSysPath);
            }

            $row['course_image'] = $thumbWebPath;
        }
    }

    $tplPath = 'skill/drh_report.tpl';
    $tpl->assign('action', $action);
    $tpl->assign('courses', $courses);
    $tpl->assign('skills', $skills);
    $tpl->assign('selected_course', $selectedCourse);
    $tpl->assign('selected_skill', $selectedSkill);
    $tpl->assign('report_title', $reportTitle);
}

if (empty($tableRows)) {
    Display::addFlash(Display::return_message(get_lang('NoResults')));
}
$tpl->assign('rows', $tableRows);
$templateName = $tpl->get_template($tplPath);
$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
