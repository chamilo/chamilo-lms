<?php
/* For licensing terms, see /license.txt */

/**
 * List all certificates filtered by session/course and month/year.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Christian Fasanando (UI refactor & toolbar actions)
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$isAllowedToTrack = api_is_platform_admin(true) || api_is_student_boss();

if (!$isAllowedToTrack) {
    api_not_allowed(true);
}

$this_section = SECTION_TRACKING;

$interbreadcrumb[] = [
    'url' => api_is_student_boss()
        ? '#'
        : api_get_path(WEB_CODE_PATH).'my_space/index.php?'.api_get_cidreq(),
    'name' => get_lang('Reporting'),
];

// ---------------------------------------------------------------------
// Read filters
// ---------------------------------------------------------------------
$selectedSession = !empty($_POST['session']) ? (int) $_POST['session'] : 0;
$selectedCourse = !empty($_POST['course']) ? (int) $_POST['course'] : 0;
$selectedMonth = !empty($_POST['month']) ? (int) $_POST['month'] : 0;
$selectedYear = isset($_POST['year']) && '' !== trim($_POST['year'])
    ? trim($_POST['year'])
    : null;
$selectedStudent = !empty($_POST['student']) ? (int) $_POST['student'] : 0;

$userId = api_get_user_id();
$sessions = $courses = $months = $students = [0 => get_lang('Select')];
$userList = [];

// Sessions list depends on role.
if (api_is_student_boss()) {
    $userGroup = new UserGroupModel();
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
    $sessions[$session['id']] = $session['title'];
}

$selfUrl = api_get_self();

// ---------------------------------------------------------------------
// Courses list (by session if selected, otherwise by coach)
// ---------------------------------------------------------------------
if ($selectedSession > 0) {
    if (!SessionManager::isValidId($selectedSession)) {
        Display::addFlash(
            Display::return_message(get_lang('The session could not be found'))
        );

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
    $months[$key] = sprintf('%02d', $key);
}

// ---------------------------------------------------------------------
// Compute certificates list according to filters
// ---------------------------------------------------------------------
$exportAllLink = null;
$certificateStudents = [];

$searchSessionAndCourse = $selectedSession > 0 && $selectedCourse > 0;
$searchCourseOnly = $selectedSession <= 0 && $selectedCourse > 0;
$searchStudentOnly = $selectedStudent > 0;

if ($searchSessionAndCourse || $searchCourseOnly) {
    $selectedCourseInfo = api_get_course_info_by_id($selectedCourse);

    if (empty($selectedCourseInfo)) {
        Display::addFlash(
            Display::return_message(get_lang('This course could not be found'))
        );

        header("Location: $selfUrl");
        exit;
    }

    $gradebookCategories = Category::load(
        null,
        null,
        $selectedCourse,
        null,
        false,
        $selectedSession
    );

    $gradebook = null;

    if (!empty($gradebookCategories)) {
        $gradebook = current($gradebookCategories);
    }

    if (null !== $gradebook) {
        // Build “export all certificates” link for this course.
        $exportAllLink = api_get_path(WEB_CODE_PATH)
            .'gradebook/gradebook_display_certificate.php?';
        $exportAllLink .= http_build_query(
            [
                'action' => 'export_all_certificates',
                'cidReq' => $selectedCourseInfo['code'],
                'id_session' => 0,
                'gidReq' => 0,
                'cat_id' => $gradebook->get_id(),
            ]
        );

        $sessionName = api_get_session_name($selectedSession);
        $courseName = api_get_course_info($selectedCourseInfo['code'])['title'];

        $studentList = GradebookUtils::get_list_users_certificates(
            $gradebook->get_id()
        );

        $certificateStudents = [];

        if (is_array($studentList) && !empty($studentList)) {
            foreach ($studentList as $student) {
                if (api_is_student_boss() && !in_array($student['user_id'], $userList, true)) {
                    continue;
                }

                $certificateStudent = [
                    'fullName' => api_get_person_name(
                        $student['firstname'],
                        $student['lastname']
                    ),
                    'sessionName' => $sessionName,
                    'courseName' => $courseName,
                    'certificates' => [],
                ];

                $studentCertificates =
                    GradebookUtils::get_list_gradebook_certificates_by_user_id(
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

                    // Apply month/year filters.
                    if ($selectedMonth > 0 && empty($selectedYear)) {
                        if ($creationMonth !== sprintf('%02d', $selectedMonth)) {
                            continue;
                        }
                    } elseif ($selectedMonth <= 0 && !empty($selectedYear)) {
                        if ($creationYear !== $selectedYear) {
                            continue;
                        }
                    } elseif ($selectedMonth > 0 && !empty($selectedYear)) {
                        if ($creationMonthYear !== sprintf('%02d %s', $selectedMonth, $selectedYear)) {
                            continue;
                        }
                    }

                    $certificateStudent['certificates'][] = [
                        'createdAt' => api_convert_and_format_date(
                            $certificate['created_at']
                        ),
                        'id' => $certificate['id'],
                        'path_certificate' => $certificate['path_certificate'] ?? '',
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
        Display::addFlash(
            Display::return_message(get_lang('No user'))
        );

        header("Location: $selfUrl");
        exit;
    }

    $sessionList = SessionManager::getSessionsFollowedByUser($selectedStudent);

    foreach ($sessionList as $session) {
        $sessionCourseList = SessionManager::get_course_list_by_session_id(
            $session['id']
        );

        foreach ($sessionCourseList as $sessionCourse) {
            $gradebookCategories = Category::load(
                null,
                null,
                $sessionCourse['real_id'],
                null,
                false,
                $session['id']
            );

            $gradebook = null;

            if (!empty($gradebookCategories)) {
                $gradebook = current($gradebookCategories);
            }

            if (null !== $gradebook) {
                $sessionName = $session['title'];
                $courseName = $sessionCourse['title'];

                $certificateStudent = [
                    'fullName' => $selectedStudentInfo['complete_name'],
                    'sessionName' => $sessionName,
                    'courseName' => $courseName,
                    'certificates' => [],
                ];

                $studentCertificates =
                    GradebookUtils::get_list_gradebook_certificates_by_user_id(
                        $selectedStudent,
                        $gradebook->get_id()
                    );

                if (!is_array($studentCertificates) || empty($studentCertificates)) {
                    continue;
                }

                foreach ($studentCertificates as $certificate) {
                    $certificateStudent['certificates'][] = [
                        'createdAt' => api_convert_and_format_date(
                            $certificate['created_at']
                        ),
                        'id' => $certificate['id'],
                        'path_certificate' => $certificate['path_certificate'] ?? '',
                    ];
                }

                if (count($certificateStudent['certificates']) > 0) {
                    $certificateStudents[] = $certificateStudent;
                }
            }
        }
    }
}

// ---------------------------------------------------------------------
// View
// ---------------------------------------------------------------------
$templateTitle = get_lang('List of learner certificates');
$template = new Template($templateTitle);

// Search by session/course form.
$form = new FormValidator(
    'certificate_report_form',
    'post',
    api_get_path(WEB_CODE_PATH).'gradebook/certificate_report.php'
);
$form->addSelect(
    'session',
    get_lang('Course sessions'),
    $sessions,
    ['id' => 'session']
);
$form->addSelect(
    'course',
    get_lang('Courses'),
    $courses,
    ['id' => 'course']
);
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
            ['id' => 'year', 'placeholder' => get_lang('year')]
        ),
    ],
    null,
    get_lang('Date')
);
$form->addButtonSearch();
$form->setDefaults(
    [
        'session' => $selectedSession,
        'course' => $selectedCourse,
        'month' => $selectedMonth,
        'year' => $selectedYear,
    ]
);

$template->assign('search_form', '');

if (api_is_student_boss()) {
    // Extra filter: search certificates by learner.
    foreach ($userList as $studentId) {
        $info = api_get_user_info($studentId);
        $students[$studentId] = $info['complete_name_with_username'];
    }

    $searchForm = new FormValidator(
        'certificate_report_form',
        'post',
        api_get_path(WEB_CODE_PATH).'gradebook/certificate_report.php'
    );
    $searchForm->addSelect(
        'student',
        get_lang('Learners'),
        $students,
        ['id' => 'student']
    );
    $searchForm->addButtonSearch();
    $searchForm->setDefaults(
        [
            'student' => $selectedStudent,
        ]
    );

    $template->assign('search_form', $searchForm->returnForm());
}

// Assign core data for tpl.
$template->assign('search_by_session_form', $form->returnForm());
$template->assign('sessions', $sessions);
$template->assign('courses', $courses);
$template->assign('months', $months);
$template->assign('export_all_link', $exportAllLink);
$template->assign('certificate_students', $certificateStudents);

// ---------------------------------------------------------------------
// Toolbar actions (left/right) – same spirit as my_space/index.php
// ---------------------------------------------------------------------
$actionsLeft = '';
$actionsRight = '';

// Shortcut: view my own progress.
$actionsLeft .= Display::url(
    Display::getMdiIcon(
        'chart-box',
        'ch-tool-icon',
        null,
        32,
        get_lang('View my progress')
    ),
    api_get_path(WEB_CODE_PATH).'auth/my_progress.php'
);
// Main MySpace navigation icons.
$actionsLeft .= Display::mySpaceMenu('certificate_report');



// Optional Learning Calendar plugin entry (teachers only).
$pluginCalendarEnabled =
    'true' === api_get_plugin_setting('learning_calendar', 'enabled');

if ($pluginCalendarEnabled && api_is_teacher()) {
    $lpCalendar = \LearningCalendarPlugin::create();
    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'calendar-text',
            'ch-tool-icon',
            null,
            32,
            $lpCalendar->get_lang('Learning calendar')
        ),
        api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/start.php'
    );
}

// Right side: export-all icon (if available) and print icon.
if (!empty($exportAllLink)) {
    $actionsRight .= Display::url(
        Display::getMdiIcon(
            'download',
            'ch-tool-icon',
            null,
            32,
            get_lang('Export all certificates to PDF')
        ),
        $exportAllLink
    );
}

$actionsRight .= Display::url(
    Display::getMdiIcon(
        'printer',
        'ch-tool-icon',
        null,
        32,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print()']
);

// Assign header + toolbar to template.
$template->assign('header', $templateTitle);
$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight])
);

// ---------------------------------------------------------------------
// Content: small intro card + original tpl content
// ---------------------------------------------------------------------
$templateName = $template->get_template('gradebook/certificate_report.tpl');

$introCard = '
<div class="card mb-8 p-2">
    <div class="card-body">
        <h5 class="card-title mb-2">'.get_lang('Certificates report').'</h5>
        <p class="card-text text-muted mb-0">'.
    get_lang(
        'Filter and list learner certificates by session, course, date or learner, then export them when needed.'
    ).
    '</p>
    </div>
</div>';

$content = $introCard.$template->fetch($templateName);

$template->assign('content', $content);
$template->display_one_col_template();
