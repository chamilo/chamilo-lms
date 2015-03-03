<?php
/* For licensing terms, see /license.txt */
/**
 * List all certificates filtered by session/course and month/year
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.gradebook
 */
use \ChamiloSession as Session;

$language_file = array('gradebook', 'exercice');
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
$selectedYear = isset($_POST['year']) && !empty($_POST['year']) ? $_POST['year'] : null;

$userId = api_get_user_id();
$sessions = SessionManager::getSessionsCoachedByUser($userId);

if ($selectedSession > 0) {
    if (!SessionManager::isValidId($selectedSession)) {
        Session::write('reportErrorMessage', get_lang('NoSession'));

        header("Location: $selfUrl");
        exit;
    }

    $courses = SessionManager::get_course_list_by_session_id($selectedSession);

    if (is_array($courses)) {
        foreach ($courses as &$course) {
            $course['real_id'] = $course['id'];
        }
    }
} else {
    $courses = CourseManager::get_courses_list_by_user_id($userId);

    if (is_array($courses)) {
        foreach ($courses as &$course) {
            $courseInfo = api_get_course_info_by_id($course['real_id']);

            $course = array_merge($course, $courseInfo);
        }
    }
}

$months = array();

for ($key = 1; $key <= 12; $key++) {
    $months[] = array(
        'key' => $key,
        'name' => sprintf("%02d", $key)
    );
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
                        if ($creationMonthYear != sprintf("%d %s", $selectedMonth, $selectedYear)) {
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

$template->assign('selectedSession', $selectedSession);
$template->assign('selectedCourse', $selectedCourse);
$template->assign('selectedMonth', $selectedMonth);
$template->assign('selectedYear', $selectedYear);
$template->assign('sessions', $sessions);
$template->assign('courses', $courses);
$template->assign('months', $months);
$template->assign('exportAllLink', $exportAllLink);
$template->assign('certificateStudents', $certificateStudents);
$content = $template->fetch("default/gradebook/certificate_report.tpl");

$template->assign('content', $content);

$template->display_one_col_template();

Session::erase('reportErrorMessage');
