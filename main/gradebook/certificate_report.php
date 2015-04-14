<?php
/* For licensing terms, see /license.txt */
/**
 * List all certificates filtered by session/course and month/year
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.gradebook
 */
use \ChamiloSession as Session;

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_TRACKING;

api_block_anonymous_users();

$interbreadcrumb[] = array(
    "url" => api_get_path(WEB_CODE_PATH) . "mySpace/index.php",
    "name" => get_lang("MySpace")
);

$selectedSession = isset($_POST['session']) && !empty($_POST['session']) ? intval($_POST['session']) : 0;
$selectedCourse = isset($_POST['course']) && !empty($_POST['course']) ? intval($_POST['course']) : 0;
$selectedMonth = isset($_POST['month']) && !empty($_POST['month']) ? intval($_POST['month']) : 0;
$selectedYear = isset($_POST['year']) && !empty($_POST['year']) ? trim($_POST['year']) : null;

$userId = api_get_user_id();

$sessions = $courses = $months = [0 => get_lang('Select')];

if (api_is_student_boss()) {
    $userList = GroupPortalManager::getGroupUsersByUser($userId);
    $sessionsList = SessionManager::getSessionsFollowedForGroupAdmin($userId);
} else {
$sessionsList = SessionManager::getSessionsCoachedByUser($userId);
}

foreach ($sessionsList as $session) {
    $sessions[$session['id']] = $session['name'];
}

if ($selectedSession > 0) {
    if (!SessionManager::isValidId($selectedSession)) {
        Session::write('reportErrorMessage', get_lang('NoSession'));

        header("Location: $selfUrl");
        exit;
    }

    $coursesList = SessionManager::get_course_list_by_session_id($selectedSession);

    if (is_array($coursesList)) {
        foreach ($coursesList as &$course) {
            $course['real_id'] = $course['id'];
        }
    }
} else {
    if (api_is_student_boss()) {
        $coursesList = CourseManager::getCoursesFollowedByGroupAdmin($userId);
    } else {
    $coursesList = CourseManager::get_courses_list_by_user_id($userId);

    if (is_array($coursesList)) {
        foreach ($coursesList as &$course) {
            $courseInfo = api_get_course_info_by_id($course['real_id']);

            $course = array_merge($course, $courseInfo);
        }
    }
    }
}

foreach ($coursesList as $course) {
    $courses[$course['id']] = $course['title'];
}

for ($key = 1; $key <= 12; $key++) {
    $months[$key] = sprintf("%02d", $key);
}

$exportAllLink = null;
$certificateStudents = array();

$searchSessionAndCourse = $selectedSession > 0 && $selectedCourse > 0;
$searchCourseOnly = $selectedSession <= 0 && $selectedCourse > 0;

if ($searchSessionAndCourse || $searchCourseOnly) {
    $selectedCourseInfo = api_get_course_info_by_id($selectedCourse);

    if (empty($selectedCourseInfo)) {
        Session::write('reportErrorMessage', get_lang('NoCourse'));

        header("Location: $selfUrl");
        exit;
    }

    $gradebookCategories = Category::load(null, null, $selectedCourseInfo['code'], null, false, $selectedSession);

    $gradebook = null;

    if (!empty($gradebookCategories)) {
        $gradebook = current($gradebookCategories);
    }

    if (!is_null($gradebook)) {
        $exportAllLink = api_get_path(WEB_CODE_PATH) . "gradebook/gradebook_display_certificate.php?";
        $exportAllLink .= http_build_query(array(
            "action" => "export_all_certificates",
            "cidReq" => $selectedCourseInfo['code'],
            "id_session" => 0,
            "gidReq" => 0,
            "cat_id" => $gradebook->get_id()
        ));

        $studentList = GradebookUtils::get_list_users_certificates($gradebook->get_id());

        $certificateStudents = array();

        if (is_array($studentList) && !empty($studentList)) {
            foreach ($studentList as $student) {
                if (api_is_student_boss() && !in_array($student['user_id'], $userList)) {
                    continue;
                }

                $certificateStudent = array(
                    'fullName' => api_get_person_name($student['firstname'], $student['lastname']),
                    'certificates' => array()
                );

                $studentCertificates = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $student['user_id'],
                    $gradebook->get_id()
                );

                if (!is_array($studentCertificates) || empty($studentCertificates)) {
                    continue;
                }

                foreach ($studentCertificates as $certificate) {
                    $creationDate = new DateTime($certificate['created_at']);
                    $creationMonth = $creationDate->format('m');
                    $creationYear = $creationDate->format('Y');
                    $creationMonthYear = $creationDate->format('m Y');

                    if ($selectedMonth > 0 && empty($selectedYear)) {
                        if ($creationMonth != $selectedMonth) {
                            continue;
                        }
                    } elseif ($selectedMonth <= 0 && !empty($selectedYear)) {
                        if ($creationYear != $selectedYear) {
                            continue;
                        }
                    } elseif ($selectedMonth > 0 && !empty($selectedYear)) {
                        if ($creationMonthYear != sprintf("%02d %s", $selectedMonth, $selectedYear)) {
                            continue;
                        }
                    }

                    $certificateStudent['certificates'][] = array(
                        'createdAt' => api_convert_and_format_date($certificate['created_at']),
                        'id' => $certificate['id']
                    );
                }

                if (count($certificateStudent['certificates']) > 0) {
                    $certificateStudents[] = $certificateStudent;
                }
            }
        }
    }
}

/* View */
$template = new Template(get_lang('GradebookListOfStudentsCertificates'));

if (Session::has('reportErrorMessage')) {
    $template->assign('errorMessage', Session::read('reportErrorMessage'));
}

$form = new FormValidator(
    'certificate_report_form',
    'post',
    api_get_path(WEB_CODE_PATH) . 'gradebook/certificate_report.php'
);
$form->addHeader(get_lang('GradebookListOfStudentsCertificates'));
$form->addSelect('session', get_lang('Sessions'), $sessions, ['id' => 'session']);
$form->addSelect('course', get_lang('Courses'), $courses, ['id' => 'course']);
$form->addGroup(
    [
        $form->createElement('select', 'month', null, $months, ['id' => 'month']),
        $form->createElement('text', 'year', null, ['id' => 'year'])
    ],
    null,
    get_lang('Date')
);
$form->addButtonSearch();
$form->setDefaults([
    'session' => $selectedSession,
    'course' => $selectedCourse,
    'month' => $selectedMonth,
    'year' => $selectedYear
]);

$template->assign('form', $form->returnForm());
$template->assign('sessions', $sessions);
$template->assign('courses', $courses);
$template->assign('months', $months);
$template->assign('exportAllLink', $exportAllLink);
$template->assign('certificateStudents', $certificateStudents);
$content = $template->fetch("default/gradebook/certificate_report.tpl");

$template->assign('content', $content);

$template->display_one_col_template();

Session::erase('reportErrorMessage');
