<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use ChamiloSession as Session;

/**
 * @package chamilo.tracking
 */
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_TRACKING;
$courseId = api_get_course_id();
$courseInfo = api_get_course_info($courseId);
//keep course_code form as it is loaded (global) by the table's get_user_data
$course_code = $courseCode = $courseInfo['code'];
$sessionId = api_get_session_id();
// PERSON_NAME_DATA_EXPORT is buggy
$sortByFirstName = api_sort_by_first_name();
$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;
$origin = api_get_origin();

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] === 'csv' ? true : false;

$htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
$htmlHeadXtra[] = ' ';

$this_section = SECTION_COURSES;
if ($from === 'myspace') {
    $from_myspace = true;
    $this_section = 'session_my_space';
}

$is_allowedToTrack = Tracking::isAllowToTrack($sessionId);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

// If the user is a HR director (drh)
if (api_is_drh()) {
    // Blocking course for drh
    if (api_drh_can_access_all_session_content()) {
        // If the drh has been configured to be allowed to see all session content, give him access to the session courses
        $coursesFromSession = SessionManager::getAllCoursesFollowedByUser(api_get_user_id(), null);
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
                api_not_allowed(true);
            }
        }
    } else {
        // If the drh has *not* been configured to be allowed to see all session content,
        // then check if he has also been given access to the corresponding courses
        $coursesFollowedList = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        $coursesFollowedList = array_keys($coursesFollowedList);
        if (!in_array($courseId, $coursesFollowedList)) {
            api_not_allowed(true);
        }
    }
}

if ($export_csv) {
    if (!empty($sessionId)) {
        Session::write('id_session', $sessionId);
    }
    ob_start();
}
$columnsToHideFromSetting = api_get_configuration_value('course_log_hide_columns');
$columnsToHide = [0, 8, 9, 10, 11];
if (!empty($columnsToHideFromSetting) && isset($columnsToHideFromSetting['columns'])) {
    $columnsToHide = $columnsToHideFromSetting['columns'];
}
$columnsToHide = json_encode($columnsToHide);
$csv_content = [];

// Scripts for reporting array hide/show columns
$js = "<script>
    // hide column and display the button to unhide it
    function foldup(id) {
        $('#reporting_table .data_table tr td:nth-child(' + (id + 1) + ')').toggleClass('hide');
        $('#reporting_table .data_table tr th:nth-child(' + (id + 1) + ')').toggleClass('hide');
        $('div#unhideButtons a:nth-child(' + (id + 1) + ')').toggleClass('hide');
    }

    // add the red cross on top of each column
    function init_hide() {
        $('#reporting_table .data_table tr th').each(
            function(index) {
                $(this).prepend(
                    '<div style=\"cursor:pointer\" onclick=\"foldup(' + index + ')\">".Display::return_icon(
                        'visible.png',
                        get_lang('HideColumn'),
                        ['align' => 'absmiddle', 'hspace' => '3px'],
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
    $(function() {
        init_hide();
        var columnsToHide = ".$columnsToHide.";
        if (columnsToHide) {
            columnsToHide.forEach(function(id) {
                foldup(id);
            });
        }
    })
</script>";
$htmlHeadXtra[] = $js;

// Database table definitions.
//@todo remove this calls
$TABLETRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);

// Breadcrumbs.
if ($origin === 'resume_session') {
    $interbreadcrumb[] = [
        'url' => '../admin/index.php',
        'name' => get_lang('PlatformAdmin'),
    ];
    $interbreadcrumb[] = [
        'url' => '../session/session_list.php',
        'name' => get_lang('SessionList'),
    ];
    $interbreadcrumb[] = [
        'url' => '../session/resume_session.php?id_session='.$sessionId,
        'name' => get_lang('SessionOverview'),
    ];
}

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$nameTools = get_lang('Tracking');

$tpl = new Template($nameTools);
// Getting all the students of the course
if (empty($sessionId)) {
    // Registered students in a course outside session.
    $studentList = CourseManager::get_student_list_from_course_code($courseId);
} else {
    // Registered students in session.
    $studentList = CourseManager::get_student_list_from_course_code($courseId, true, $sessionId);
}

$nbStudents = count($studentList);
$user_ids = array_keys($studentList);
$extra_info = [];
$userProfileInfo = [];
// Getting all the additional information of an additional profile field.
if (isset($_GET['additional_profile_field'])) {
    $user_array = [];
    foreach ($studentList as $key => $item) {
        $user_array[] = $key;
    }

    foreach ($_GET['additional_profile_field'] as $fieldId) {
        // Fetching only the user that are loaded NOT ALL user in the portal.
        $userProfileInfo[$fieldId] = TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser(
            $fieldId,
            $user_array
        );

        $extra_info[$fieldId] = UserManager::get_extra_field_information($fieldId);
    }
}

Session::write('additional_user_profile_info', $userProfileInfo);
Session::write('extra_field_info', $extra_info);

Display::display_header($nameTools, 'Tracking');

$actionsLeft = TrackingCourseLog::actionsLeft('users', $sessionId);

$actionsRight = '<div class="pull-right">';
$actionsRight .= '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

$additionalParams = '';
if (isset($_GET['additional_profile_field'])) {
    foreach ($_GET['additional_profile_field'] as $fieldId) {
        $additionalParams .= '&additional_profile_field[]='.(int) $fieldId;
    }
}

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page = '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}

$actionsRight .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$additionalParams.$users_tracking_per_page.'">
     '.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';
$actionsRight .= '</div>';
// Create a search-box.
$form_search = new FormValidator(
    'search_simple',
    'GET',
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq(),
    '',
    [],
    FormValidator::LAYOUT_INLINE
);
$form_search->addHidden('from', Security::remove_XSS($from));
$form_search->addHidden('session_id', $sessionId);
$form_search->addHidden('id_session', $sessionId);
$form_search->addElement('text', 'user_keyword');
$form_search->addButtonSearch(get_lang('SearchUsers'));
echo Display::toolbarAction(
    'toolbar-courselog',
    [$actionsLeft, $form_search->returnForm(), $actionsRight]
);

$course_name = get_lang('Course').' '.$courseInfo['name'];

if ($sessionId) {
    $titleSession = Display::return_icon(
        'session.png',
        get_lang('Session'),
        [],
        ICON_SIZE_SMALL
    ).' '.api_get_session_name($sessionId);
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
    true,
    true
);

$coaches = null;
if (!empty($sessionId)) {
    $coaches = CourseManager::get_coachs_from_course_to_string(
        $sessionId,
        $courseInfo['real_id'],
        ',',
        true,
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

$showReporting = api_get_configuration_value('hide_reporting_session_list') === false;
if ($showReporting) {
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
        $urlWebCode = api_get_path(WEB_CODE_PATH);
        $isAdmin = api_is_platform_admin();
        foreach ($sessionList as $session) {
            if (!$isAdmin) {
                // Check session visibility
                $visibility = api_get_session_visibility($session['id'], api_get_course_int_id());
                if ($visibility == SESSION_INVISIBLE) {
                    continue;
                }

                // Check if is coach
                $isCoach = api_is_coach($session['id'], api_get_course_int_id());
                if (!$isCoach) {
                    continue;
                }
            }
            $url = $urlWebCode.'mySpace/course.php?session_id='.$session['id'].'&cidReq='.$courseInfo['code'];
            $html .= Display::tag('li', $icon.' '.Display::url($session['name'], $url));
        }
        $html .= '</ul>';
    }
}

$trackingColumn = isset($_GET['users_tracking_column']) ? $_GET['users_tracking_column'] : null;
$trackingDirection = isset($_GET['users_tracking_direction']) ? $_GET['users_tracking_direction'] : null;

// Show the charts part only if there are students subscribed to this course/session
if ($nbStudents > 0) {
    $usersTracking = TrackingCourseLog::get_user_data(null, $nbStudents, $trackingColumn, $trackingDirection, false);
    $numberStudentsCompletedLP = 0;
    $averageStudentsTestScore = 0;
    $scoresDistribution = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    $userScoreList = [];
    $listStudentIds = [];
    $timeStudent = [];
    $certificateCount = 0;
    $category = Category::load(
        null,
        null,
        $course_code,
        null,
        null,
        $sessionId
    );

    $hideReports = api_get_configuration_value('hide_course_report_graph');

    if ($hideReports === false) {
        foreach ($usersTracking as $userTracking) {
            $userInfo = api_get_user_info_from_username($userTracking[3]);
            if (empty($userInfo)) {
                continue;
            }
            $userId = $userInfo['user_id'];
            if ($userTracking[5] === '100%') {
                $numberStudentsCompletedLP++;
            }
            $averageStudentTestScore = substr($userTracking[7], 0, -1);
            $averageStudentsTestScore += $averageStudentTestScore;

            if ($averageStudentTestScore === '100') {
                $reducedAverage = 9;
            } else {
                $reducedAverage = floor($averageStudentTestScore / 10);
            }
            if (isset($scoresDistribution[$reducedAverage])) {
                $scoresDistribution[$reducedAverage]++;
            }
            $scoreStudent = substr($userTracking[5], 0, -1) + substr($userTracking[7], 0, -1);
            list($hours, $minutes, $seconds) = preg_split('/:/', $userTracking[4]);
            $minutes = round((3600 * $hours + 60 * $minutes + $seconds) / 60);

            $certificate = false;
            if (isset($category[0]) && $category[0]->is_certificate_available($userId)) {
                $certificate = true;
                $certificateCount++;
            }

            $listStudent = [
                'id' => $userId,
                'fullname' => $userInfo['complete_name'],
                'score' => floor($scoreStudent / 2),
                'total_time' => $minutes,
                'avatar' => $userInfo['avatar'],
                'certicate' => $certificate,
            ];
            $listStudentIds[] = $userId;
            $userScoreList[] = $listStudent;
        }

        uasort($userScoreList, 'sort_by_order');
        $averageStudentsTestScore = round($averageStudentsTestScore / $nbStudents);

        $colors = ChamiloApi::getColorPalette(true, true, 10);

        $tpl->assign('chart_colors', json_encode($colors));
        $tpl->assign('certificate_count', $certificateCount);
        $tpl->assign('score_distribution', json_encode($scoresDistribution));
        $tpl->assign('json_time_student', json_encode($userScoreList));
        $tpl->assign('students_test_score', $averageStudentsTestScore);
        $tpl->assign('students_completed_lp', $numberStudentsCompletedLP);
        $tpl->assign('number_students', $nbStudents);
        $tpl->assign('top_students', $userScoreList);

        $trackingSummaryLayout = $tpl->get_template('tracking/tracking_course_log.tpl');
        $content = $tpl->fetch($trackingSummaryLayout);

        echo $content;
    }
}

$html .= Display::page_subheader2(get_lang('StudentList'));

if ($nbStudents > 0) {
    $getLangXDays = get_lang('XDays');
    $form = new FormValidator(
        'reminder_form',
        'get',
        api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
        null,
        ['style' => 'margin-bottom: 10px'],
        FormValidator::LAYOUT_INLINE
    );
    $options = [
        2 => sprintf($getLangXDays, 2),
        3 => sprintf($getLangXDays, 3),
        4 => sprintf($getLangXDays, 4),
        5 => sprintf($getLangXDays, 5),
        6 => sprintf($getLangXDays, 6),
        7 => sprintf($getLangXDays, 7),
        15 => sprintf($getLangXDays, 15),
        30 => sprintf($getLangXDays, 30),
        'never' => get_lang('Never'),
    ];
    $el = $form->addSelect(
        'since',
        Display::returnFontAwesomeIcon('warning').get_lang('RemindInactivesLearnersSince'),
        $options,
        ['disable_js' => true, 'class' => 'col-sm-3']
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

    $html .= $form->returnForm();

    if ($export_csv) {
        $csv_content = [];
        //override the SortableTable "per page" limit if CSV
        $_GET['users_tracking_per_page'] = 1000000;
    }

    $table = new SortableTableFromArray(
        $usersTracking,
        1,
        20,
        'users_tracking'
    );
    $table->total_number_of_items = $nbStudents;

    $parameters['cidReq'] = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
    $parameters['id_session'] = $sessionId;
    $parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

    $table->set_additional_parameters($parameters);

    $headers = [];
    // tab of header texts
    $table->set_header(0, get_lang('OfficialCode'), true);
    $headers['official_code'] = get_lang('OfficialCode');
    if ($sortByFirstName) {
        $table->set_header(1, get_lang('FirstName'), true);
        $table->set_header(2, get_lang('LastName'), true);
        $headers['firstname'] = get_lang('FirstName');
        $headers['lastname'] = get_lang('LastName');
    } else {
        $table->set_header(1, get_lang('LastName'), true);
        $table->set_header(2, get_lang('FirstName'), true);
        $headers['lastname'] = get_lang('LastName');
        $headers['firstname'] = get_lang('FirstName');
    }
    $table->set_header(3, get_lang('Login'), false);
    $headers['login'] = get_lang('Login');

    $table->set_header(
        4,
        get_lang('TrainingTime').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('CourseTimeInfo'), ['align' => 'absmiddle', 'hspace' => '3px']),
        false,
        ['style' => 'width:110px;']
    );
    $headers['training_time'] = get_lang('TrainingTime');
    $table->set_header(5, get_lang('CourseProgress').'&nbsp;'.
        Display::return_icon(
            'info3.gif',
            get_lang('ScormAndLPProgressTotalAverage'),
            ['align' => 'absmiddle', 'hspace' => '3px']
        ),
        false,
        ['style' => 'width:110px;']
    );
    $headers['course_progress'] = get_lang('CourseProgress');

    $table->set_header(6, get_lang('ExerciseProgress').'&nbsp;'.
        Display::return_icon(
            'info3.gif',
            get_lang('ExerciseProgressInfo'),
            ['align' => 'absmiddle', 'hspace' => '3px']
        ),
        false,
        ['style' => 'width:110px;']
    );
    $headers['exercise_progress'] = get_lang('ExerciseProgress');
    $table->set_header(7, get_lang('ExerciseAverage').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ExerciseAverageInfo'), ['align' => 'absmiddle', 'hspace' => '3px']),
        false,
        ['style' => 'width:110px;']
    );
    $headers['exercise_average'] = get_lang('ExerciseAverage');
    $table->set_header(8, get_lang('Score').'&nbsp;'.
        Display::return_icon(
            'info3.gif',
            get_lang('ScormAndLPTestTotalAverage'),
            ['align' => 'absmiddle', 'hspace' => '3px']
        ),
        false,
        ['style' => 'width:110px;']
    );
    $headers['score'] = get_lang('Score');
    $table->set_header(9, get_lang('Student_publication'), false);
    $headers['student_publication'] = get_lang('Student_publication');
    $table->set_header(10, get_lang('Messages'), false);
    $headers['messages'] = get_lang('Messages');
    $table->set_header(11, get_lang('Classes'));
    $headers['clasess'] = get_lang('Classes');

    if (empty($sessionId)) {
        $table->set_header(12, get_lang('Survey'), false);
        $headers['survey'] = get_lang('Survey');
    } else {
        $table->set_header(12, get_lang('RegisteredDate'), false);
        $headers['registered_at'] = get_lang('RegisteredDate');
    }
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
        $headers['Details'] = get_lang('Details');
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
                'onclick' => "foldup($index); return false;",
            ]
        );
        $index++;
    }
    $html .= '</div>';

    $html .= '<div id="reporting_table">';
    $html .= $table->return_table();
    $html .= '</div>';
} else {
    $html .= Display::return_message(get_lang('NoUsersInCourse'), 'warning', true);
}

echo Display::panel($html, $titleSession);

// Send the csv file if asked.
if ($export_csv) {
    $csv_headers = [];
    $csv_headers[] = get_lang('OfficialCode');
    if ($sortByFirstName) {
        $csv_headers[] = get_lang('FirstName');
        $csv_headers[] = get_lang('LastName');
    } else {
        $csv_headers[] = get_lang('LastName');
        $csv_headers[] = get_lang('FirstName');
    }
    $csv_headers[] = get_lang('Login');
    $csv_headers[] = get_lang('TrainingTime');
    $csv_headers[] = get_lang('CourseProgress');
    $csv_headers[] = get_lang('ExerciseProgress');
    $csv_headers[] = get_lang('ExerciseAverage');
    $csv_headers[] = get_lang('Score');
    $csv_headers[] = get_lang('Student_publication');
    $csv_headers[] = get_lang('Messages');

    if (empty($sessionId)) {
        $csv_headers[] = get_lang('Survey');
    } else {
        $csv_headers[] = get_lang('RegistrationDate');
    }

    $csv_headers[] = get_lang('FirstLoginInCourse');
    $csv_headers[] = get_lang('LatestLoginInCourse');

    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $csv_headers[] = $extra_info[$fieldId]['display_text'];
        }
    }
    ob_end_clean();

    $csvContentInSession = Session::read('csv_content');

    // Adding headers before the content.
    array_unshift($csvContentInSession, $csv_headers);

    if ($sessionId) {
        $sessionInfo = api_get_session_info($sessionId);
        $sessionDates = SessionManager::parseSessionDates($sessionInfo);

        array_unshift($csvContentInSession, [get_lang('Date'), $sessionDates['access']]);
        array_unshift($csvContentInSession, [get_lang('SessionName'), $sessionInfo['name']]);
    }

    Export::arrayToCsv($csvContentInSession, 'reporting_student_list');
    exit;
}
Display::display_footer();

function sort_by_order($a, $b)
{
    return $a['score'] <= $b['score'];
}
