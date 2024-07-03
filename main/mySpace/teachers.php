<?php
/* For licensing terms, see /license.txt */
/**
 * Teacher report.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$export_csv = isset($_GET['export']) && 'csv' == $_GET['export'] ? true : false;
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
$active = isset($_GET['active']) ? intval($_GET['active']) : 1;
$sleepingDays = isset($_GET['sleeping_days']) ? intval($_GET['sleeping_days']) : null;
$nameTools = get_lang('Teachers');
$this_section = SECTION_TRACKING;

$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && !isset($_GET["type"])) {
    $interbreadcrumb[] = ["url" => "teachers.php", "name" => get_lang('Teachers')];
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && isset($_GET["type"]) && $_GET["type"] == "coach") {
    $interbreadcrumb[] = ["url" => "coaches.php", "name" => get_lang('Tutors')];
}

function get_count_users()
{
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $active = isset($_GET['active']) ? intval($_GET['active']) : 1;
    $sleepingDays = isset($_GET['sleeping_days']) ? intval($_GET['sleeping_days']) : null;

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }

    $count = SessionManager::getCountUserTracking(
        $keyword,
        $active,
        $lastConnectionDate,
        null,
        null,
        COURSEMANAGER
    );

    return $count;
}

function get_users($from, $limit, $column, $direction)
{
    $active = isset($_GET['active']) ? $_GET['active'] : 1;
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $sleepingDays = isset($_GET['sleeping_days']) ? intval($_GET['sleeping_days']) : null;
    $sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }

    $is_western_name_order = api_is_western_name_order();
    $coach_id = api_get_user_id();

    $drhLoaded = false;
    if (api_is_drh() && api_drh_can_access_all_session_content()) {
        $students = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
            'drh_all',
            api_get_user_id(),
            false,
            $from,
            $limit,
            $column,
            $direction,
            $keyword,
            $active,
            $lastConnectionDate,
            null,
            null,
            COURSEMANAGER
        );
        $drhLoaded = true;
    }

    $checkSessionVisibility = api_get_configuration_value('show_users_in_active_sessions_in_tracking');
    if (false == $drhLoaded) {
        $students = UserManager::getUsersFollowedByUser(
            api_get_user_id(),
            COURSEMANAGER,
            false,
            false,
            false,
            $from,
            $limit,
            $column,
            $direction,
            $active,
            $lastConnectionDate,
            COURSEMANAGER,
            $keyword,
            $checkSessionVisibility
        );
    }

    $all_datas = [];
    $url = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php';
    foreach ($students as $student_data) {
        $student_id = $student_data['user_id'];
        $student_data = api_get_user_info($student_id);
        if (isset($_GET['id_session'])) {
            $courses = Tracking::get_course_list_in_session_from_student($student_id, $_GET['id_session']);
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

        $urlDetails = $url."?student=$student_id&origin=teacher_details";
        if (isset($_GET['id_coach']) && 0 != intval($_GET['id_coach'])) {
            $urlDetails = $url."?student=$student_id&id_coach=$coach_id&id_session=$sessionId";
        }

        $row = [];
        if ($is_western_name_order) {
            $row[] = Display::url($student_data['firstname'], $urlDetails);
            $row[] = Display::url($student_data['lastname'], $urlDetails);
        } else {
            $row[] = $student_data['lastname'];
            $row[] = $student_data['firstname'];
        }
        $string_date = Tracking::get_last_connection_date($student_id, true);
        $first_date = Tracking::get_first_connection_date($student_id);
        $row[] = $first_date;
        $row[] = $string_date;

        $detailsLink = Display::url(
            Display::return_icon('2rightarrow.png', get_lang('Details').' '.$student_data['username']),
            $urlDetails,
            ['id' => 'details_'.$student_data['username']]
        );
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
            api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
        ),
        Display::url(Display::return_icon('user.png', get_lang('Students'), [], ICON_SIZE_MEDIUM), 'student.php'),
        Display::url(
            Display::return_icon('teacher_na.png', get_lang('Trainers'), [], ICON_SIZE_MEDIUM),
            'teachers.php'
        ),
        Display::url(Display::return_icon('course.png', get_lang('Courses'), [], ICON_SIZE_MEDIUM), 'course.php'),
        Display::url(
            Display::return_icon('session.png', get_lang('Sessions'), [], ICON_SIZE_MEDIUM),
            'session.php'
        ),
    ];

    $nb_menu_items = count($menu_items);
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            $actionsLeft .= $item;
        }
    }
}

$actionsRight = '';
$actionsRight .= Display::url(
    Display::return_icon('printer.png', get_lang('Print'), [], ICON_SIZE_MEDIUM),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);
$actionsRight .= Display::url(
    Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
    api_get_self().'?export=csv&keyword='.$keyword
);

$toolbar = Display::toolbarAction('toolbar-teachers', [$actionsLeft, $actionsRight]);

$table = new SortableTable(
    'tracking_teachers',
    'get_count_users',
    'get_users',
    ($is_western_name_order xor $sort_by_first_name) ? 1 : 0,
    10
);

$params = [
    'keyword' => $keyword,
    'active' => $active,
    'sleeping_days' => $sleepingDays,
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

$form = new FormValidator('search_user', 'get', api_get_path(WEB_CODE_PATH).'mySpace/teachers.php');
$form = Tracking::setUserSearchForm($form);
$form->setDefaults($params);

if ($export_csv) {
    // send the csv file if asked
    $content = $table->get_table_data();
    foreach ($content as &$row) {
        $row[3] = strip_tags($row[3]);
        unset($row[4]);
    }
    $csv_content = array_merge($csv_header, $content);
    ob_end_clean();
    Export::arrayToCsv($csv_content, 'reporting_teacher_list');
    exit;
} else {
    Display::display_header($nameTools);
    echo $toolbar;
    $page_title = get_lang('Teachers');
    echo Display::page_subheader($page_title);
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
