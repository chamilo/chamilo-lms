<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_TRACKING;

$courseInfo = api_get_course_info(api_get_course_id());
$courseCode = $courseInfo['code'];
$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && 'csv' == $_GET['export'] ? true : false;
$session_id = isset($_REQUEST['id_session']) ? intval($_REQUEST['id_session']) : 0;

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    $from_myspace = true;
    $this_section = 'session_my_space';
}

// Access restrictions.
$is_allowedToTrack = Tracking::isAllowToTrack($session_id);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
    exit;
}

// If the user is a HR director (drh)
if (api_is_drh()) {
    // Blocking course for drh
    if (api_drh_can_access_all_session_content()) {
        // If the drh has been configured to be allowed to see all session content,
        // give him access to the session courses
        $coursesFromSession = SessionManager::getAllCoursesFollowedByUser(
            api_get_user_id(),
            null
        );

        $coursesFromSessionCodeList = [];
        if (!empty($coursesFromSession)) {
            foreach ($coursesFromSession as $course) {
                $coursesFromSessionCodeList[$course['code']] = $course['code'];
            }
        }

        $coursesFollowedList = CourseManager::get_courses_followed_by_drh(api_get_user_id());

        if (!empty($coursesFollowedList)) {
            $coursesFollowedList = array_keys($coursesFollowedList);
        }

        if (!in_array($courseCode, $coursesFollowedList)) {
            if (!in_array($courseCode, $coursesFromSessionCodeList)) {
                api_not_allowed();
            }
        }
    } else {
        // If the drh has *not* been configured to be allowed to see all session content,
        // then check if he has also been given access to the corresponding courses
        $coursesFollowedList = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        $coursesFollowedList = array_keys($coursesFollowedList);
        if (!in_array(api_get_course_id(), $coursesFollowedList)) {
            api_not_allowed(true);
            exit;
        }
    }
}

if ($export_csv) {
    if (!empty($session_id)) {
        Session::write('id_session', $session_id);
    }
    ob_start();
}
$columnsToHideFromSetting = api_get_configuration_value('course_log_hide_columns');
$columnsToHide = empty($columnsToHideFromSetting) ? [0, 8, 9, 10, 11] : $columnsToHideFromSetting;
$columnsToHide = json_encode($columnsToHide);

$csv_content = [];
// Database table definitions.
//@todo remove this calls
$TABLETRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);

$sessionId = api_get_session_id();

// Breadcrumbs.
if (isset($_GET['origin']) && 'resume_session' == $_GET['origin']) {
    $interbreadcrumb[] = [
        'url' => '../admin/index.php',
        'name' => get_lang('Administration'),
    ];
    $interbreadcrumb[] = [
        'url' => '../session/session_list.php',
        'name' => get_lang('Session list'),
    ];
    $interbreadcrumb[] = [
        'url' => '../session/resume_session.php?id_session='.$sessionId,
        'name' => get_lang('Session overview'),
    ];
}

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$nameTools = get_lang('Reporting');

// getting all the students of the course
if (empty($session_id)) {
    // Registered students in a course outside session.
    $a_students = CourseManager::get_student_list_from_course_code(
        api_get_course_id()
    );
} else {
    // Registered students in session.
    $a_students = CourseManager::get_student_list_from_course_code(
        api_get_course_id(),
        true,
        $sessionId
    );
}

$nbStudents = count($a_students);

// Display the header.
Display::display_header($nameTools, 'Tracking');

/* MAIN CODE */

$actionsLeft = Display::return_icon(
    'user_na.png',
    get_lang('StudentsReporting'),
    [],
    ICON_SIZE_MEDIUM
);
$actionsLeft .= Display::url(
    Display::return_icon('group.png', get_lang('Group reporting'), [], ICON_SIZE_MEDIUM),
    'course_log_groups.php?'.api_get_cidreq()
);
$actionsLeft .= Display::url(
    Display::return_icon('course.png', get_lang('CourseReporting'), [], ICON_SIZE_MEDIUM),
    'course_log_tools.php?'.api_get_cidreq()
);

/*$actionsLeft .= Display::url(
    Display::return_icon('tools.png', get_lang('ResourcesReporting'), [], ICON_SIZE_MEDIUM),
    'course_log_resources.php?'.api_get_cidreq()
);*/
$actionsLeft .= Display::url(
    Display::return_icon('quiz.png', get_lang('ExamReporting'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'tracking/exams.php?'.api_get_cidreq()
);

if (!empty($sessionId)) {
    $actionsLeft .= Display::url(
        Display::return_icon('attendance_list.png', get_lang('Logins'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'attendance/index.php?'.api_get_cidreq().'&action=calendar_logins'
    );
}

$actionsRight = '<div class="pull-right">';
$actionsRight .= '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page = '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}
$actionsRight .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$users_tracking_per_page.'">
     '.Display::return_icon('export_csv.png', get_lang('CSV export'), '', ICON_SIZE_MEDIUM).'</a>';
$actionsRight .= '</div>';
// Create a search-box.
$form_search = new FormValidator(
    'search_simple',
    'GET',
    api_get_path(WEB_CODE_PATH).'tracking/total_time.php?'.api_get_cidreq(),
    '',
    [],
    FormValidator::LAYOUT_INLINE
);
$form_search->addElement('hidden', 'from', Security::remove_XSS($from));
$form_search->addElement('hidden', 'session_id', $sessionId);
$form_search->addElement('hidden', 'id_session', $sessionId);
$form_search->addElement('text', 'user_keyword');
$form_search->addButtonSearch(get_lang('Search users'));
echo Display::toolbarAction(
    'toolbar-courselog',
    [$actionsLeft, $form_search->returnForm(), $actionsRight]
);

$course_name = get_lang('Course').' '.$courseInfo['name'];
if ($session_id) {
    $titleSession = Display::return_icon(
        'session.png',
        get_lang('Session'),
        [],
        ICON_SIZE_SMALL
    ).' '.api_get_session_name($session_id);
    $titleCourse = Display::return_icon(
        'course.png',
        get_lang('Course'),
        [],
        ICON_SIZE_SMALL
    ).' '.$course_name;
} else {
    $titleSession = Display::return_icon(
        'course.png',
        get_lang('Course'),
        [],
        ICON_SIZE_SMALL
    ).' '.$courseInfo['name'];
}
$teacherList = CourseManager::getTeacherListFromCourseCodeToString(
    $courseInfo['code'],
    ',',
    false,
    true
);

$coaches = null;
if (!empty($session_id)) {
    $coaches = CourseManager::get_coachs_from_course_to_string(
        $session_id,
        $courseInfo['real_id'],
        ',',
        false,
        true
    );
}
$html = '';

if (!empty($teacherList)) {
    $html .= Display::page_subheader2(get_lang('Trainers'));
    $html .= $teacherList;
}

if (!empty($coaches)) {
    $html .= Display::page_subheader2(get_lang('Coaches'));
    $html .= $coaches;
}

if (api_is_platform_admin(true) ||
    api_is_session_general_coach()
) {
    $sessionList = SessionManager::get_session_by_course($courseInfo['real_id']);

    if (!empty($sessionList)) {
        $html .= Display::page_subheader2(get_lang('Session list'));
        $icon = Display::return_icon(
            'session.png',
            null,
            null,
            ICON_SIZE_TINY
        );

        $html .= '<ul class="session-list">';
        foreach ($sessionList as $session) {
            $url = api_get_path(WEB_CODE_PATH).'my_space/course.php?sid='
                .$session['id'].'&cidReq='.$courseInfo['code'];
            $html .= Display::tag('li', $icon.' '.Display::url($session['name'], $url));
        }
        $html .= '</ul>';
    }
}

$html .= Display::page_subheader2(get_lang('Learners list'));

// PERSON_NAME_DATA_EXPORT is buggy
$is_western_name_order = api_is_western_name_order();

if (count($a_students) > 0) {
    $all_datas = [];
    $user_ids = array_keys($a_students);

    $table = new SortableTable(
        'users_tracking',
        ['TrackingCourseLog', 'get_number_of_users'],
        ['TrackingCourseLog', 'getTotalTimeReport'],
        (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2
    );

    $table->setDataFunctionParams(['cid' => api_get_course_int_id(), 'sid' => $session_id]);

    $parameters['cid'] = api_get_course_int_id();
    $parameters['sid'] = $session_id;
    $parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

    $table->set_additional_parameters($parameters);
    $headers = [];
    // tab of header texts
    $table->set_header(0, get_lang('Code'), true);
    $headers['official_code'] = get_lang('Code');
    if ($is_western_name_order) {
        $table->set_header(1, get_lang('First name'), true);
        $headers['firstname'] = get_lang('First name');
        $table->set_header(2, get_lang('Last name'), true);
        $headers['lastname'] = get_lang('Last name');
    } else {
        $table->set_header(1, get_lang('Last name'), true);
        $headers['lastname'] = get_lang('Last name');
        $table->set_header(2, get_lang('First name'), true);
        $headers['firstname'] = get_lang('First name');
    }
    $table->set_header(3, get_lang('Login'), false);
    $headers['login'] = get_lang('Login');

    $table->set_header(
        4,
        get_lang('Time').'&nbsp;'.
        Display::return_icon(
            'info3.gif',
            get_lang('Time spent in the course'),
            ['align' => 'absmiddle', 'hspace' => '3px']
        ),
        false,
        ['style' => 'width:110px;']
    );
    $headers['training_time'] = get_lang('Time');

    $table->set_header(
        5,
        get_lang('Total learnpath time').'&nbsp;'.
        Display::return_icon(
            'info3.gif',
            get_lang('Total learnpath time'),
            ['align' => 'absmiddle', 'hspace' => '3px']
        ),
        false,
        ['style' => 'width:110px;']
    );
    $headers['total_time_lp'] = get_lang('Total learnpath time');

    $table->set_header(6, get_lang('First access to course'), false);
    $headers['first_login'] = get_lang('First access to course');
    $table->set_header(7, get_lang('Latest access in course'), false);
    $headers['latest_login'] = get_lang('Latest access in course');

    // Display the table
    $html .= "<div id='reporting_table'>";
    $html .= $table->return_table();
    $html .= "</div>";
} else {
    $html .= Display::return_message(get_lang('No users in course'), 'warning', true);
}
echo Display::panel($html, $titleSession);
Display::display_footer();
