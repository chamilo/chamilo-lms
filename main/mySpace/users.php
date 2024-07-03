<?php

/* For licensing terms, see /license.txt */

/**
 * Report on users followed (filtered by status given in URL).
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() ||
    api_is_student_boss();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$nameTools = get_lang('Users');
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'] ? true : false;
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
$active = isset($_GET['active']) ? intval($_GET['active']) : 1;
$sleepingDays = isset($_GET['sleeping_days']) ? intval($_GET['sleeping_days']) : null;
$status = isset($_GET['status']) ? Security::remove_XSS($_GET['status']) : null;

$webCodePath = api_get_path(WEB_CODE_PATH);

$this_section = SECTION_TRACKING;

$interbreadcrumb[] = [
    "url" => "index.php",
    "name" => get_lang('MySpace'),
];

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && !isset($_GET["type"])) {
    $interbreadcrumb[] = [
        "url" => "teachers.php",
        "name" => get_lang('Teachers'),
    ];
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && isset($_GET["type"]) && $_GET["type"] === "coach") {
    $interbreadcrumb[] = ["url" => "coaches.php", "name" => get_lang('Tutors')];
}

function get_count_users()
{
    $sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;
    $active = isset($_GET['active']) ? (int) $_GET['active'] : 1;
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $status = !empty($_GET['status']) ? Security::remove_XSS($_GET['status']) : null;

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }

    $usersCount = 0;
    $allowDhrAccessToAllStudents = api_get_configuration_value('drh_allow_access_to_all_students');
    if ($allowDhrAccessToAllStudents) {
        $conditions = [];
        if (isset($status)) {
            $conditions['status'] = $status;
        }
        if (isset($active)) {
            $conditions['active'] = (int) $active;
        }
        $usersCount = UserManager::get_user_list(
            $conditions,
            [],
            false,
            false,
            null,
            $keyword,
            $lastConnectionDate,
            true
        );
    } else {
        $usersCount = SessionManager::getCountUserTracking(
            $keyword,
            $active,
            $lastConnectionDate,
            null,
            null,
            $status
        );
    }

    return $usersCount;
}

function get_users($from, $limit, $column, $direction)
{
    $active = isset($_GET['active']) ? $_GET['active'] : 1;
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;
    $sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
    $status = !empty($_GET['status']) ? Security::remove_XSS($_GET['status']) : null;

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }
    $is_western_name_order = api_is_western_name_order();
    $coach_id = api_get_user_id();
    $drhLoaded = false;

    if (api_is_drh()) {
        if (api_drh_can_access_all_session_content()) {
            $students = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'drh_all',
                api_get_user_id(),
                false,
                $from,
                $limit,
                '',
                $direction,
                $keyword,
                $active,
                $lastConnectionDate,
                null,
                null,
                $status
            );
            $drhLoaded = true;
        }
        $allowDhrAccessToAllStudents = api_get_configuration_value('drh_allow_access_to_all_students');
        if ($allowDhrAccessToAllStudents) {
            $conditions = [];
            if (isset($status)) {
                $conditions['status'] = $status;
            }
            if (isset($active)) {
                $conditions['active'] = (int) $active;
            }
            $students = UserManager::get_user_list(
                $conditions,
                [],
                $from,
                $limit,
                null,
                $keyword,
                $lastConnectionDate
            );
            $drhLoaded = true;
        }
    }

    if (false === $drhLoaded) {
        $checkSessionVisibility = api_get_configuration_value('show_users_in_active_sessions_in_tracking');
        $students = UserManager::getUsersFollowedByUser(
            api_get_user_id(),
            $status,
            false,
            false,
            false,
            $from,
            $limit,
            '',
            $direction,
            $active,
            $lastConnectionDate,
            COURSEMANAGER,
            $keyword,
            $checkSessionVisibility
        );
    }

    $all_datas = [];
    foreach ($students as $student_data) {
        $student_id = $student_data['user_id'];
        $student_data = api_get_user_info($student_id);
        if (isset($_GET['id_session'])) {
            $courses = Tracking::get_course_list_in_session_from_student($student_id, $sessionId);
        }

        $avg_time_spent = $avg_student_score = $avg_student_progress = 0;
        $nb_courses_student = 0;
        if (!empty($courses)) {
            foreach ($courses as $course_code) {
                $courseInfo = api_get_course_info($course_code);
                $courseId = $courseInfo['real_id'];

                if (CourseManager::is_user_subscribed_in_course($student_id, $course_code, true)) {
                    $avg_time_spent += Tracking::get_time_spent_on_the_course(
                        $student_id,
                        $courseId,
                        $_GET['id_session']
                    );
                    $my_average = Tracking::get_avg_student_score($student_id, $course_code);
                    if (is_numeric($my_average)) {
                        $avg_student_score += $my_average;
                    }
                    $avg_student_progress += Tracking::get_avg_student_progress($student_id, $course_code);
                    $nb_courses_student++;
                }
            }
        }

        if ($nb_courses_student > 0) {
            $avg_time_spent = $avg_time_spent / $nb_courses_student;
            $avg_student_score = $avg_student_score / $nb_courses_student;
            $avg_student_progress = $avg_student_progress / $nb_courses_student;
        } else {
            $avg_time_spent = null;
            $avg_student_score = null;
            $avg_student_progress = null;
        }

        $row = [];
        if ($is_western_name_order) {
            $row[] = $student_data['firstname'];
            $row[] = $student_data['lastname'];
        } else {
            $row[] = $student_data['lastname'];
            $row[] = $student_data['firstname'];
        }

        $string_date = Tracking::get_last_connection_date($student_id, true);
        $first_date = Tracking::get_first_connection_date($student_id);
        $row[] = $first_date;
        $row[] = $string_date;

        if (isset($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
            $detailsLink = '<a href="myStudents.php?student='.$student_id.'&id_coach='.$coach_id.'&id_session='.$sessionId.'">
				            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'</a>';
        } else {
            $detailsLink = '<a href="myStudents.php?student='.$student_id.'">
				            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'</a>';
        }

        $row[] = $detailsLink;
        $all_datas[] = $row;
    }

    return $all_datas;
}

if ($export_csv) {
    $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
} else {
    $is_western_name_order = api_is_western_name_order();
}

$sort_by_first_name = api_sort_by_first_name();
$actionsLeft = '';

if (api_is_drh()) {
    $menu_items = [
        Display::url(
            Display::return_icon('statistics.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
            $webCodePath.'auth/my_progress.php'
        ),
        Display::url(
            Display::return_icon('user_na.png', get_lang('Students'), [], ICON_SIZE_MEDIUM),
            '#'
        ),
        Display::url(
            Display::return_icon('teacher.png', get_lang('Trainers'), [], ICON_SIZE_MEDIUM),
            'teachers.php'
        ),
        Display::url(
            Display::return_icon('course.png', get_lang('Courses'), [], ICON_SIZE_MEDIUM),
            'course.php'
        ),
        Display::url(
            Display::return_icon('session.png', get_lang('Sessions'), [], ICON_SIZE_MEDIUM),
            'session.php'
        ),
        Display::url(
            Display::return_icon('skills.png', get_lang('Skills'), [], ICON_SIZE_MEDIUM),
            $webCodePath.'social/my_skills_report.php'
        ),
    ];

    $nb_menu_items = count($menu_items);
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            $actionsLeft .= $item;
        }
    }
} elseif (api_is_student_boss()) {
    $actionsLeft .= Display::url(
        Display::return_icon('statistics.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
        $webCodePath.'auth/my_progress.php'
    );
    $actionsLeft .= Display::url(
        Display::return_icon('user_na.png', get_lang('Students'), [], ICON_SIZE_MEDIUM),
        '#'
    );
    $actionsLeft .= Display::url(
        Display::return_icon('skills.png', get_lang('Skills'), [], ICON_SIZE_MEDIUM),
        $webCodePath.'social/my_skills_report.php'
    );
    $actionsLeft .= Display::url(
        Display::return_icon('statistics.png', get_lang("CompanyReport"), [], ICON_SIZE_MEDIUM),
        $webCodePath.'mySpace/company_reports.php'
    );
    $actionsLeft .= Display::url(
        Display::return_icon(
            'certificate_list.png',
            get_lang('GradebookSeeListOfStudentsCertificates'),
            [],
            ICON_SIZE_MEDIUM
        ),
        $webCodePath.'gradebook/certificate_report.php'
    );
}

$actionsRight = Display::url(
    Display::return_icon('printer.png', get_lang('Print'), [], ICON_SIZE_MEDIUM),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);
$actionsRight .= Display::url(
    Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
    api_get_self().'?export=csv&keyword='.$keyword
);

$toolbar = Display::toolbarAction('toolbar-user', [$actionsLeft, $actionsRight]);

$itemPerPage = 10;
$perPage = api_get_configuration_value('my_space_users_items_per_page');
if ($perPage) {
    $itemPerPage = (int) $perPage;
}

$table = new SortableTable(
    'tracking_student',
    'get_count_users',
    'get_users',
    ($is_western_name_order xor $sort_by_first_name) ? 1 : 0,
    $itemPerPage
);

$params = [
    'keyword' => $keyword,
    'active' => $active,
    'sleeping_days' => $sleepingDays,
    'status' => $status,
];
$table->set_additional_parameters($params);

if ($is_western_name_order) {
    $table->set_header(0, get_lang('FirstName'), false);
    $table->set_header(1, get_lang('LastName'), false);
} else {
    $table->set_header(0, get_lang('LastName'), false);
    $table->set_header(1, get_lang('FirstName'), false);
}

$table->set_header(2, get_lang('FirstLogin'), false);
$table->set_header(3, get_lang('LastConnexion'), false);
$table->set_header(4, get_lang('Details'), false);

if ($export_csv) {
    if ($is_western_name_order) {
        $csv_header[] = [
            get_lang('FirstName'),
            get_lang('LastName'),
            get_lang('FirstLogin'),
            get_lang('LastConnexion'),
        ];
    } else {
        $csv_header[] = [
            get_lang('LastName'),
            get_lang('FirstName'),
            get_lang('FirstLogin'),
            get_lang('LastConnexion'),
        ];
    }
}

$form = new FormValidator(
    'search_user',
    'get',
    $webCodePath.'mySpace/users.php'
);
$form->addElement(
    'select',
    'status',
    get_lang('Status'),
    [
        '' => '',
        STUDENT => get_lang('Student'),
        COURSEMANAGER => get_lang('Teacher'),
        DRH => get_lang('DRH'),
    ]
);
$form = Tracking::setUserSearchForm($form);
$form->setDefaults($params);

if ($export_csv) {
    // send the csv file if asked
    $content = $table->get_table_data();
    foreach ($content as &$row) {
        unset($row[4]);
    }
    $csv_content = array_merge($csv_header, $content);
    ob_end_clean();
    Export::arrayToCsv($csv_content, 'reporting_student_list');
    exit;
} else {
    Display::display_header($nameTools);
    echo $toolbar;
    echo Display::page_subheader($nameTools);
    if (isset($active)) {
        if ($active) {
            $activeLabel = get_lang('ActiveUsers');
        } else {
            $activeLabel = get_lang('InactiveUsers');
        }
        echo Display::page_subheader2($activeLabel);
    }
    $form->display();
    $table->display();
}

Display::display_footer();
