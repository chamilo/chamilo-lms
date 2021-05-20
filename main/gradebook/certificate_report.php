<?php
/* For licensing terms, see /license.txt */

/**
 * List all certificates filtered by session/course and month/year.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$is_allowedToTrack = api_is_platform_admin(true) || api_is_student_boss();

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

$this_section = SECTION_TRACKING;

$interbreadcrumb[] = [
    "url" => api_is_student_boss() ? "#" : api_get_path(WEB_CODE_PATH)."mySpace/index.php?".api_get_cidreq(),
    "name" => get_lang("MySpace"),
];

$selectedSession = isset($_POST['session']) && !empty($_POST['session']) ? intval($_POST['session']) : 0;
$selectedCourse = isset($_POST['course']) && !empty($_POST['course']) ? intval($_POST['course']) : 0;
$selectedMonth = isset($_POST['month']) && !empty($_POST['month']) ? intval($_POST['month']) : 0;
$selectedYear = isset($_POST['year']) && !empty($_POST['year']) ? trim($_POST['year']) : null;
$selectedStudent = isset($_POST['student']) && !empty($_POST['student']) ? intval($_POST['student']) : 0;

$userId = api_get_user_id();
$sessions = $courses = $months = $students = [0 => get_lang('Select')];
$userList = [];
if (api_is_student_boss()) {
    $userGroup = new UserGroup();
    $userList = $userGroup->getGroupUsersByUser($userId);
    $sessionsList = SessionManager::getSessionsFollowedForGroupAdmin($userId);
} else {
    $sessionsList = SessionManager::getSessionsCoachedByUser(
        $userId,
        false,
        api_is_platform_admin()
    );
}

foreach ($sessionsList as $session) {
    $sessions[$session['id']] = $session['name'];
}

if ($selectedSession > 0) {
    if (!SessionManager::isValidId($selectedSession)) {
        Display::addFlash(Display::return_message(get_lang('NoSession')));

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
        $coursesList = CourseManager::get_courses_list_by_user_id(
            $userId,
            false,
            true
        );

        if (is_array($coursesList)) {
            foreach ($coursesList as &$course) {
                $courseInfo = api_get_course_info_by_id($course['real_id']);

                $course = array_merge($course, $courseInfo);
            }
        }
    }
}

foreach ($coursesList as $course) {
    if (isset($course['real_id'])) {
        $courses[$course['real_id']] = $course['title'];
    } else {
        $courses[$course['id']] = $course['title'];
    }
}

for ($key = 1; $key <= 12; $key++) {
    $months[$key] = sprintf("%02d", $key);
}

$exportAllLink = null;
$certificateStudents = [];
$searchSessionAndCourse = $selectedSession > 0 && $selectedCourse > 0;
$searchCourseOnly = $selectedSession <= 0 && $selectedCourse > 0;
$searchStudentOnly = $selectedStudent > 0;

if ($searchSessionAndCourse || $searchCourseOnly) {
    $selectedCourseInfo = api_get_course_info_by_id($selectedCourse);

    if (empty($selectedCourseInfo)) {
        Display::addFlash(Display::return_message(get_lang('NoCourse')));

        header("Location: $selfUrl");
        exit;
    }

    $gradebookCategories = Category::load(
        null,
        null,
        $selectedCourseInfo['code'],
        null,
        false,
        $selectedSession
    );

    $gradebook = null;

    if (!empty($gradebookCategories)) {
        $gradebook = current($gradebookCategories);
    }

    if (!is_null($gradebook)) {
        $exportAllLink = GradebookUtils::returnJsExportAllCertificates(
            '#btn-export-all',
            $gradebook->get_id(),
            $selectedCourseInfo['code']
        );

        $sessionName = api_get_session_name($selectedSession);
        $courseName = api_get_course_info($selectedCourseInfo['code'])['title'];

        $studentList = GradebookUtils::get_list_users_certificates($gradebook->get_id());

        $certificateStudents = [];

        if (is_array($studentList) && !empty($studentList)) {
            foreach ($studentList as $student) {
                if (api_is_student_boss() && !in_array($student['user_id'], $userList)) {
                    continue;
                }

                $certificateStudent = [
                    'fullName' => api_get_person_name($student['firstname'], $student['lastname']),
                    'sessionName' => $sessionName,
                    'courseName' => $courseName,
                    'certificates' => [],
                ];

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

                    $certificateStudent['certificates'][] = [
                        'createdAt' => api_convert_and_format_date($certificate['created_at']),
                        'id' => $certificate['id'],
                    ];
                }

                if (count($certificateStudent['certificates']) > 0) {
                    $certificateStudents[] = $certificateStudent;
                }
            }
        }
    }
} elseif ($searchStudentOnly) {
    $selectedStudentInfo = api_get_user_info($selectedStudent);

    if (empty($selectedStudentInfo)) {
        Display::addFlash(Display::return_message(get_lang('NoUser')));

        header('Location: '.$selfUrl);
        exit;
    }

    $sessionList = SessionManager::getSessionsFollowedByUser($selectedStudent);

    foreach ($sessionList as $session) {
        $sessionCourseList = SessionManager::get_course_list_by_session_id($session['id']);

        foreach ($sessionCourseList as $sessionCourse) {
            $gradebookCategories = Category::load(
                null,
                null,
                $sessionCourse['code'],
                null,
                false,
                $session['id']
            );

            $gradebook = null;

            if (!empty($gradebookCategories)) {
                $gradebook = current($gradebookCategories);
            }

            if (!is_null($gradebook)) {
                $sessionName = $session['name'];
                $courseName = $sessionCourse['title'];

                $certificateStudent = [
                    'fullName' => $selectedStudentInfo['complete_name'],
                    'sessionName' => $sessionName,
                    'courseName' => $courseName,
                    'certificates' => [],
                ];

                $studentCertificates = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $selectedStudent,
                    $gradebook->get_id()
                );

                if (!is_array($studentCertificates) || empty($studentCertificates)) {
                    continue;
                }

                foreach ($studentCertificates as $certificate) {
                    $certificateStudent['certificates'][] = [
                        'createdAt' => api_convert_and_format_date($certificate['created_at']),
                        'id' => $certificate['id'],
                    ];
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

$form = new FormValidator(
    'certificate_report_form',
    'post',
    api_get_path(WEB_CODE_PATH).'gradebook/certificate_report.php'
);
$form->addSelect('session', get_lang('Sessions'), $sessions, ['id' => 'session']);
$form->addSelect('course', get_lang('Courses'), $courses, ['id' => 'course']);
$form->addGroup(
    [
        $form->createElement(
            'select',
            'month',
            null,
            $months,
            ['id' => 'month']
        ),
        $form->createElement(
            'text',
            'year',
            null,
            ['id' => 'year', 'placeholder' => get_lang('Year')]
        ),
    ],
    null,
    get_lang('Date')
);
$form->addButtonSearch();
$form->setDefaults([
    'session' => $selectedSession,
    'course' => $selectedCourse,
    'month' => $selectedMonth,
    'year' => $selectedYear,
]);

if (api_is_student_boss()) {
    foreach ($userList as $studentId) {
        $students[$studentId] = api_get_user_info($studentId)['complete_name_with_username'];
    }

    $searchForm = new FormValidator(
        'certificate_report_form',
        'post',
        api_get_path(WEB_CODE_PATH).'gradebook/certificate_report.php'
    );
    $searchForm->addSelect('student', get_lang('Students'), $students, ['id' => 'student']);
    $searchForm->addButtonSearch();
    $searchForm->setDefaults([
        'student' => $selectedStudent,
    ]);

    $template->assign('search_form', $searchForm->returnForm());
}

$template->assign('search_by_session_form', $form->returnForm());
$template->assign('sessions', $sessions);
$template->assign('courses', $courses);
$template->assign('months', $months);
$template->assign('export_all_link', $exportAllLink);
$template->assign('certificate_students', $certificateStudents);
$templateName = $template->get_template('gradebook/certificate_report.tpl');
$content = $template->fetch($templateName);
$template->assign('content', $content);
$template->display_one_col_template();
