<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @package chamilo.tracking
 */

$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;

// Including the global initialization file
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_TRACKING;

$courseInfo = api_get_course_info(api_get_course_id());
$courseCode = $courseInfo['code'];
$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$session_id = isset($_REQUEST['id_session']) ? intval($_REQUEST['id_session']) : 0;

if ($from == 'myspace') {
    $from_myspace = true;
    $this_section = "session_my_space";
} else {
    $this_section = SECTION_COURSES;
}

// Access restrictions.
$is_allowedToTrack =
    api_is_platform_admin() ||
    SessionManager::user_is_general_coach(api_get_user_id(), $session_id) ||
    api_is_allowed_to_create_course() ||
    api_is_session_admin() ||
    api_is_drh() ||
    api_is_course_tutor() ||
    api_is_course_admin();

if (!$is_allowedToTrack) {
    api_not_allowed(true);
    exit;
}

// If the user is a HR director (drh)
if (api_is_drh()) {
    // Blocking course for drh
    if (api_drh_can_access_all_session_content()) {
        // If the drh has been configured to be allowed to see all session content, give him access to the session courses
        $coursesFromSession = SessionManager::getAllCoursesFollowedByUser(
            api_get_user_id(),
            null
        );

        $coursesFromSessionCodeList = array();
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
        $_SESSION['id_session'] = $session_id;
    }
    ob_start();
}
$columnsToHideFromSetting = api_get_configuration_value('course_log_hide_columns');
$columnsToHide = empty($columnsToHideFromSetting) ? array(0, 8, 9, 10, 11) : $columnsToHideFromSetting;
$columnsToHide = json_encode($columnsToHide);

$csv_content = array();
// Scripts for reporting array hide/show columns
$js = "<script>
    // hide column and display the button to unhide it
    function foldup(in_id) {
        $('#reporting_table .data_table tr td:nth-child(' + (in_id + 1) + ')').toggleClass('hide');
        $('#reporting_table .data_table tr th:nth-child(' + (in_id + 1) + ')').toggleClass('hide');
        $('div#unhideButtons a:nth-child(' + (in_id + 1) + ')').toggleClass('hide');
    }

    // add the red cross on top of each column
    function init_hide() {
        $('#reporting_table .data_table tr th').each(
            function(index) {
                $(this).prepend(
                    '<div style=\"cursor:pointer\" onclick=\"foldup(' + index + ')\">" . Display::return_icon(
                        'visible.png',
                        get_lang('HideColumn'),
                        array('align' => 'absmiddle', 'hspace' => '3px'),
                        ICON_SIZE_SMALL
                     )."</div>'
                );
            }
        );
    }

    // hide some column at startup
    // be sure that these columns always exists
    // see headers = array();
    // tab of header texts
    $(document).ready( function() {
        init_hide();
        var columnsToHide = ".$columnsToHide.";
        columnsToHide.forEach(function(id) {
            foldup(id);
        });
    })
</script>";

$htmlHeadXtra[] = "<style type='text/css'>
    .secLine {background-color : #E6E6E6;}
    .content {padding-left : 15px;padding-right : 15px; }
    .specialLink{color : #0000FF;}
    div#reporting_table table th {
      vertical-align:top;
    }
</style>";
$htmlHeadXtra[] .= $js;

// Database table definitions.
//@todo remove this calls
$TABLETRACK_ACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2 = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$TABLETRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);

$sessionId = api_get_session_id();

// Breadcrumbs.
if (isset($_GET['origin']) && $_GET['origin'] == 'resume_session') {
    $interbreadcrumb[] = array('url' => '../admin/index.php', 'name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => '../session/session_list.php', 'name' => get_lang('SessionList'));
    $interbreadcrumb[] = array('url' => '../session/resume_session.php?id_session='.$sessionId, 'name' => get_lang('SessionOverview'));
}

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$nameTools = get_lang('Tracking');

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
$extra_info = array();
$userProfileInfo = [];
// Getting all the additional information of an additional profile field.
if (isset($_GET['additional_profile_field'])) {
    $user_array = array();
    foreach ($a_students as $key => $item) {
        $user_array[] = $key;
    }

    foreach ($_GET['additional_profile_field'] as $fieldId) {
         // Fetching only the user that are loaded NOT ALL user in the portal.
        $userProfileInfo[$fieldId] = TrackingCourseLog::get_addtional_profile_information_of_field_by_user(
            $fieldId,
            $user_array
        );

        $extra_info[$fieldId] = UserManager::get_extra_field_information(
            $fieldId
        );
    }
}

Session::write('additional_user_profile_info', $userProfileInfo);
Session::write('extra_field_info', $extra_info);

// Display the header.
Display::display_header($nameTools, 'Tracking');

/* MAIN CODE */

$actionsLeft = Display::return_icon('user_na.png', get_lang('StudentsTracking'), array(), ICON_SIZE_MEDIUM);
$actionsLeft .= Display::url(
    Display::return_icon('group.png', get_lang('GroupReporting'), array(), ICON_SIZE_MEDIUM),
    'course_log_groups.php?'.api_get_cidreq()
);
$actionsLeft .= Display::url(
    Display::return_icon('course.png', get_lang('CourseTracking'), array(), ICON_SIZE_MEDIUM),
    'course_log_tools.php?'.api_get_cidreq()
);

$actionsLeft .= Display::url(
    Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), ICON_SIZE_MEDIUM),
    'course_log_resources.php?'.api_get_cidreq()
);
$actionsLeft .= Display::url(
    Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), ICON_SIZE_MEDIUM),
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

$addional_param = '';
if (isset($_GET['additional_profile_field'])) {
    foreach ($_GET['additional_profile_field'] as $fieldId) {
        $addional_param .= '&additional_profile_field[]='. (int) $fieldId;
    }
}

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page = '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}
$actionsRight .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.$users_tracking_per_page.'">
     '.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';
$actionsRight .= '</div>';
// Create a search-box.
$form_search = new FormValidator(
    'search_simple',
    'GET',
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq(),
    '',
    array(),
    FormValidator::LAYOUT_INLINE
);
$form_search->addElement('hidden', 'from', Security::remove_XSS($from));
$form_search->addElement('hidden', 'session_id', $sessionId);
$form_search->addElement('hidden', 'id_session', $sessionId);
$form_search->addElement('text', 'user_keyword');
$form_search->addButtonSearch(get_lang('SearchUsers'));
echo Display::toolbarAction(
    'toolbar-courselog',
    [$actionsLeft, $form_search->returnForm(), $actionsRight]
);

$course_name = get_lang('Course').' '.$courseInfo['name'];
if ($session_id) {
    $titleSession = Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.api_get_session_name($session_id);
    $titleCourse = Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$course_name;
} else {
    $titleSession = Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$courseInfo['name'];
}
$teacherList = CourseManager::get_teacher_list_from_course_code_to_string(
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
    $html .= Display::page_subheader2(get_lang('Teachers'));
    $html .= $teacherList;
}

if (!empty($coaches)) {
    $html .= Display::page_subheader2(get_lang('Coaches'));
    $html .= $coaches;
}

if (api_is_platform_admin(true) || api_is_session_general_coach()) {
    $sessionList = SessionManager::get_session_by_course($courseInfo['real_id']);

    if (!empty($sessionList)) {
        $html .= Display::page_subheader2(get_lang('SessionList'));
        $icon = Display::return_icon(
            'session.png',
            null,
            null,
            ICON_SIZE_TINY
        );

        $html .= '<ul class="session-list">';
        foreach ($sessionList as $session) {
            $url = api_get_path(WEB_CODE_PATH).'mySpace/course.php?session_id='
                .$session['id'].'&cidReq='.$courseInfo['code'];
            $html .= Display::tag('li', $icon.' '.Display::url($session['name'], $url));
        }
        $html .= '</ul>';
    }
}

$html .= Display::page_subheader2(get_lang('StudentList'));

// PERSON_NAME_DATA_EXPORT is buggy
$is_western_name_order = api_is_western_name_order();

if (count($a_students) > 0) {
    $getLangXDays = get_lang('XDays');
    $form = new FormValidator(
        'reminder_form',
        'get',
        api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
        null,
        ['style' => 'margin-bottom: 10px'],
        FormValidator::LAYOUT_INLINE
    );
    $options = array(
        2 => sprintf($getLangXDays, 2),
        3 => sprintf($getLangXDays, 3),
        4 => sprintf($getLangXDays, 4),
        5 => sprintf($getLangXDays, 5),
        6 => sprintf($getLangXDays, 6),
        7 => sprintf($getLangXDays, 7),
        15 => sprintf($getLangXDays, 15),
        30 => sprintf($getLangXDays, 30),
        'never' => get_lang('Never')
    );
    $el = $form->addSelect(
        'since',
        Display::returnFontAwesomeIcon('warning').get_lang('RemindInactivesLearnersSince'),
        $options,
        ['class' => 'col-sm-3']
    );
    $el->setSelected(7);

    $form->addElement('hidden', 'action', 'add');
    $form->addElement('hidden', 'remindallinactives', 'true');
    $form->addElement('hidden', 'cidReq', $courseInfo['code']);
    $form->addElement('hidden', 'id_session', api_get_session_id());
    $form->addButtonSend(get_lang('SendNotification'));

    $extra_field_select = TrackingCourseLog::display_additional_profile_fields();

    if (!empty($extra_field_select)) {
        $html .= $extra_field_select;
    }

    $html .= $form->return_form();

    if ($export_csv) {
        $csv_content = array();
        //override the SortableTable "per page" limit if CSV
        $_GET['users_tracking_per_page'] = 1000000;
    }

    $all_datas = array();
    $course_code = $_course['id'];

    $user_ids = array_keys($a_students);

    $table = new SortableTable(
        'users_tracking',
        array('TrackingCourseLog', 'get_number_of_users'),
        array('TrackingCourseLog', 'get_user_data'),
        (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);

    $parameters['cidReq'] = Security::remove_XSS($_GET['cidReq']);
    $parameters['id_session'] = $session_id;
    $parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

    $table->set_additional_parameters($parameters);
    $headers = array();
    // tab of header texts
    $table->set_header(0, get_lang('OfficialCode'), true);
    $headers['official_code'] = get_lang('OfficialCode');
    if ($is_western_name_order) {
        $table->set_header(1, get_lang('FirstName'), true);
        $headers['firstname'] = get_lang('FirstName');
        $table->set_header(2, get_lang('LastName'), true);
        $headers['lastname'] = get_lang('LastName');
    } else {
        $table->set_header(1, get_lang('LastName'), true);
        $headers['lastname'] = get_lang('LastName');
        $table->set_header(2, get_lang('FirstName'), true);
        $headers['firstname'] = get_lang('FirstName');
    }
    $table->set_header(3, get_lang('Login'), false);
    $headers['login'] = get_lang('Login');

    $table->set_header(4, get_lang('TrainingTime').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('CourseTimeInfo'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $headers['training_time'] = get_lang('TrainingTime');
    $table->set_header(5, get_lang('CourseProgress').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ScormAndLPProgressTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $headers['course_progress'] = get_lang('CourseProgress');

    $table->set_header(6, get_lang('ExerciseProgress').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ExerciseProgressInfo'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $headers['exercise_progress'] = get_lang('ExerciseProgress');
    $table->set_header(7, get_lang('ExerciseAverage').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ExerciseAverageInfo'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $headers['exercise_average'] = get_lang('ExerciseAverage');
    $table->set_header(8, get_lang('Score').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $headers['score'] = get_lang('Score');
    $table->set_header(9, get_lang('Student_publication'), false);
    $headers['student_publication'] = get_lang('Student_publication');
    $table->set_header(10, get_lang('Messages'), false);
    $headers['messages'] = get_lang('Messages');
    $table->set_header(11, get_lang('Classes'));
    $headers['clasess'] = get_lang('Classes');

    if (empty($session_id)) {
        $table->set_header(12, get_lang('Survey'), false);
        $headers['survey'] = get_lang('Survey');
        $table->set_header(13, get_lang('FirstLoginInCourse'), false);
        $headers['first_login'] = get_lang('FirstLoginInCourse');
        $table->set_header(14, get_lang('LatestLoginInCourse'), false);
        $headers['latest_login'] = get_lang('LatestLoginInCourse');
        if (isset($_GET['additional_profile_field'])) {
            $counter = 15;
            foreach ($_GET['additional_profile_field'] as $fieldId) {
                $table->set_header($counter, $extra_info[$fieldId]['display_text'], false);
                $headers[$extra_info[$fieldId]['variable']] = $extra_info[$fieldId]['display_text'];
                $counter++;
            }

            $table->set_header($counter, get_lang('Details'), false);
            $headers['details'] = get_lang('Details');
        } else {
            $table->set_header(15, get_lang('Details'), false);
            $headers['details'] = get_lang('Details');
        }
    } else {
        $table->set_header(12, get_lang('FirstLoginInCourse'), false);
        $headers['first_login'] = get_lang('FirstLoginInCourse');
        $table->set_header(13, get_lang('LatestLoginInCourse'), false);
        $headers['latest_login'] = get_lang('LatestLoginInCourse');

        if (isset($_GET['additional_profile_field'])) {
            $counter = 15;
            foreach ($_GET['additional_profile_field'] as $fieldId) {
                $table->set_header($counter, $extra_info[$fieldId]['display_text'], false);
                $headers[$extra_info[$fieldId]['variable']] = $extra_info[$fieldId]['display_text'];
                $counter++;
            }
        } else {
            $table->set_header(14, get_lang('Details'), false);
            $headers['Details'] = get_lang('Details');
        }
    }
    // display buttons to un hide hidden columns
    $html .= '<div id="unhideButtons" class="btn-toolbar">';
    $index = 0;
    $getLangDisplayColumn = get_lang('DisplayColumn');
    foreach ($headers as $header) {
        $html .= Display::toolbarButton(
            $header,
            '#',
            'arrow-right',
            'default',
            [
                'title' => htmlentities("$getLangDisplayColumn \"$header\"", ENT_QUOTES),
                'class' => 'hide',
                'onclick' => "foldup($index); return false;"
            ]
        );

        $index++;
    }
    $html .= "</div>";
    // Display the table
    $html .= "<div id='reporting_table'>";
    $html .= $table->return_table();
    $html .= "</div>";
} else {
    $html .= Display::return_message(get_lang('NoUsersInCourse'), 'warning', true);
}
echo Display::panel($html, $titleSession);
// Send the csv file if asked.
if ($export_csv) {
    $csv_headers = array();

    $csv_headers[] = get_lang('OfficialCode', '');
    if ($is_western_name_order) {
        $csv_headers[] = get_lang('FirstName', '');
        $csv_headers[] = get_lang('LastName', '');
    } else {
        $csv_headers[] = get_lang('LastName', '');
        $csv_headers[] = get_lang('FirstName', '');
    }
    $csv_headers[] = get_lang('Login', ''); //
    $csv_headers[] = get_lang('TrainingTime', '');
    $csv_headers[] = get_lang('CourseProgress', '');
    $csv_headers[] = get_lang('ExerciseProgress', '');
    $csv_headers[] = get_lang('ExerciseAverage', '');
    $csv_headers[] = get_lang('Score', '');
    $csv_headers[] = get_lang('Student_publication', '');
    $csv_headers[] = get_lang('Messages', '');

    if (empty($session_id)) {
        $csv_headers[] = get_lang('Survey');
    }

    $csv_headers[] = get_lang('FirstLoginInCourse', '');
    $csv_headers[] = get_lang('LatestLoginInCourse', '');

    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $csv_headers[] = $extra_info[$fieldId]['display_text'];
        }
    }
    ob_end_clean();
    array_unshift($csv_content, $csv_headers); // Adding headers before the content.

    Export::arrayToCsv($csv_content, 'reporting_student_list');
    exit;
}
Display::display_footer();
