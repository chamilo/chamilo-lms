<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.tracking
 */

/* INIT SECTION */

$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;

// Language files that need to be included.
$language_file = array('admin', 'tracking', 'scorm', 'exercice');

// Including the global initialization file
require_once '../inc/global.inc.php';
$current_course_tool = TOOL_TRACKING;

$courseInfo = api_get_course_info(api_get_course_id());
$courseCode = api_get_course_id();
$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$session_id = intval($_REQUEST['id_session']);

if ($from == 'myspace') {
    $from_myspace = true;
    $this_section = "session_my_space";
} else {
    $this_section = SECTION_COURSES;
}

// Access restrictions.
$is_allowedToTrack =
    api_is_platform_admin() ||
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
        $coursesFromSession = SessionManager::getAllCoursesFollowedByUser(api_get_user_id(), null);
        if (!empty($coursesFromSession)) {
            $coursesFromSession = array_keys($coursesFromSession);
        }

        $coursesFollowedList = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        if (!empty($coursesFollowedList)) {
            $coursesFollowedList = array_keys($coursesFollowedList);
        }
        if (!in_array($courseCode, $coursesFollowedList)) {
            if (!in_array($courseCode, $coursesFromSession)) {
                api_not_allowed();
            }
        }
    } else {
        // If the drh has *not* been configured to be allowed to see all session content, then check if he has also been given access to the corresponding courses
        $coursesFollowedList = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        $coursesFollowedList = array_keys($coursesFollowedList);
        if (!in_array(api_get_course_id(), $coursesFollowedList)) {
            api_not_allowed(true);
            exit;
        }
    }
}

// Including additional libraries.

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scorm.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scormItem.class.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';
require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';
require_once api_get_path(SYS_CODE_PATH).'survey/survey.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';

if ($export_csv) {
    if (!empty($session_id)) {
        $_SESSION['id_session'] = $session_id;
    }
    ob_start();
}

$csv_content = array();
// Scripts for reporting array hide/show columns
$js = "<script>
        // hide column and display the button to unhide it
        function foldup(in_id) {
            $('#reporting_table .data_table tr td:nth-child('+in_id+')').fadeToggle();
            $('#reporting_table .data_table tr th:nth-child('+in_id+')').fadeToggle();
            $('div#unhideButtons span:nth-child('+in_id+')').fadeToggle();
        }

        // add the red cross on top of each column
        function init_hide() {
            $('div#reporting_table table tr th').each(
                function(index) {
                    num_index = index + 1;
                   // $(this).prepend('<div style=\"cursor:pointer\" onclick=\"foldup('+num_index+')\">".Display :: return_icon('visible.png', get_lang('HideColumn'), array('align' => 'absmiddle', 'hspace' => '3px'), 22)."</div>');
                }
            );
        }

        // hide some column at startup
        // be sure that these columns always exists
        // see tab_table_header = array();    // tab of header texts
        $(document).ready( function() {
            //init_hide();
            foldup(1);
            foldup(9);
            foldup(10);
            foldup(11);
            foldup(12);
        })
    </script>";

$htmlHeadXtra[] = "<style type='text/css'>
    .secLine {background-color : #E6E6E6;}
    .content {padding-left : 15px;padding-right : 15px; }
    .specialLink{color : #0000FF;}
    /* Style for reporting array hide/show columns */
    .unhide_button {
        cursor : pointer;
        border:1px solid black;
        background-color: #FAFAFA;
        padding: 5px;
        border-radius : 3px;
        margin-right:3px;
    }
    div#reporting_table table th {
      vertical-align:top;
    }
</style>";
$htmlHeadXtra[] .= $js;

// Database table definitions.
//@todo remove this calls
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$TABLETRACK_EXERCISES 	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE	        = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user             = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ              = Database::get_course_table(TABLE_QUIZ_TEST);

// Breadcrumbs.
if (isset($_GET['origin']) && $_GET['origin'] == 'resume_session') {
    $interbreadcrumb[] = array('url' => '../admin/index.php','name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => '../admin/session_list.php','name' => get_lang('SessionList'));
    $interbreadcrumb[] = array('url' => '../admin/resume_session.php?id_session='.api_get_session_id(), 'name' => get_lang('SessionOverview'));
}

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$nameTools = get_lang('Tracking');

// Display the header.
Display::display_header($nameTools, 'Tracking');

// getting all the students of the course
if (empty($session_id)) {
    // Registered students in a course outside session.
    $a_students = CourseManager::get_student_list_from_course_code(api_get_course_id());
} else {
    // Registered students in session.
    $a_students = CourseManager::get_student_list_from_course_code(api_get_course_id(), true, api_get_session_id());
}

$nbStudents = count($a_students);

// Getting all the additional information of an additional profile field.
if (isset($_GET['additional_profile_field']) && is_numeric($_GET['additional_profile_field'])) {
    $user_array = array();
    foreach ($a_students as $key => $item) {
        $user_array[] = $key;
    }
    // Fetching only the user that are loaded NOT ALL user in the portal.
    $additional_user_profile_info = TrackingCourseLog::get_addtional_profile_information_of_field_by_user(
        $_GET['additional_profile_field'],
        $user_array
    );
    $extra_info = UserManager::get_extra_field_information($_GET['additional_profile_field']);
}

/* MAIN CODE */

echo '<div class="actions">';

echo Display::return_icon('user_na.png', get_lang('StudentsTracking'), array(), 32);
echo Display::url(Display::return_icon('course.png', get_lang('CourseTracking'), array(), 32), 'course_log_tools.php?'.api_get_cidreq());
echo Display::url(Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), 32), 'course_log_resources.php?'.api_get_cidreq());
echo Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), 32), api_get_path(WEB_CODE_PATH).'tracking/exams.php?'.api_get_cidreq());

echo '<span style="float:right; padding-top:0px;">';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';

$addional_param = '';
if (isset($_GET['additional_profile_field'])) {
    $addional_param ='additional_profile_field='.intval($_GET['additional_profile_field']);
}
$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page= '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}
echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.$users_tracking_per_page.'">
     '.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';

echo '</span>';
echo '</div>';


echo '<div class="actions">';
// Create a search-box.
$form_search = new FormValidator(
    'search_simple',
    'GET',
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq(),
    '',
    array('class' => 'form-search'),
    false
);
$renderer = $form_search->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span>');
$form_search->addElement('hidden', 'from', Security::remove_XSS($from));
$form_search->addElement('hidden', 'session_id', api_get_session_id());
$form_search->addElement('hidden', 'id_session', api_get_session_id());
$form_search->addElement('text', 'user_keyword');
$form_search->addElement('style_submit_button', 'submit', get_lang('SearchUsers'), 'class="search"');
$form_search->display();
echo '</div>';

$course_name = get_lang('Course').' '.$courseInfo['name'];

if ($session_id) {
    echo Display::page_subheader(
        Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.api_get_session_name($session_id).' '.
        Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$course_name
    );
} else {
    echo Display::page_subheader(
        Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$courseInfo['name']
    );
}

$teacherList = CourseManager::get_teacher_list_from_course_code_to_string(
    $courseInfo['code'],
    ',',
    false
);

$coaches = null;
if (!empty($session_id)) {
    $coaches = CourseManager::get_coachs_from_course_to_string(
        $session_id,
        $courseInfo['code'],
        ',',
        false
    );
}

if (!empty($teacherList)) {
    echo Display::page_subheader2(get_lang('Teachers'));
    echo $teacherList;
}

if (!empty($coaches)) {
    echo Display::page_subheader2(get_lang('Coaches'));
    echo $coaches;
}


$sessionList = SessionManager::get_session_by_course($courseInfo['code']);
if (!empty($sessionList)) {
    echo Display::page_subheader2(get_lang('SessionList'));
    $sessionToShow = array();
    foreach ($sessionList as $session) {
        $url = api_get_path(WEB_CODE_PATH).'mySpace/course.php?session_id='.$session['id'].'&cidReq='.$courseInfo['code'];
        $sessionToShow[] = Display::url($session['name'], $url);
    }
    echo implode(', ', $sessionToShow);
}

echo Display::page_subheader2(get_lang('StudentList'));

if (count($a_students) > 0) {
    $form = new FormValidator('reminder_form', 'get', api_get_path(REL_CODE_PATH).'announcements/announcements.php');
    $renderer = $form->defaultRenderer();
    $renderer->setElementTemplate('<span>{label} {element}</span>&nbsp;<button class="save" type="submit">'.get_lang('SendNotification').'</button>','since');
    $options = array (
        2 => '2 '.get_lang('Days'),
        3 => '3 '.get_lang('Days'),
        4 => '4 '.get_lang('Days'),
        5 => '5 '.get_lang('Days'),
        6 => '6 '.get_lang('Days'),
        7 => '7 '.get_lang('Days'),
        15 => '15 '.get_lang('Days'),
        30 => '30 '.get_lang('Days'),
        'never' => get_lang('Never')
    );

    $el = $form->addElement('select', 'since', '<img width="ICON_SIZE_SMALL" align="middle" src="'.api_get_path(WEB_IMG_PATH).'messagebox_warning.gif" border="0" />'.get_lang('RemindInactivesLearnersSince'), $options);
    $el->setSelected(7);

    $form->addElement('hidden', 'action', 'add');
    $form->addElement('hidden', 'remindallinactives', 'true');

    $extra_field_select = TrackingCourseLog::display_additional_profile_fields();

    if (!empty($extra_field_select)) {
        echo $extra_field_select;
    }

    $form->display();

    // PERSON_NAME_DATA_EXPORT is buggy
    $is_western_name_order = api_is_western_name_order();

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

    $parameters['cidReq'] 		= Security::remove_XSS($_GET['cidReq']);
    $parameters['id_session'] 	= $session_id;
    $parameters['from'] 		= isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

    $table->set_additional_parameters($parameters);
    $tab_table_header = array();    // tab of header texts
    $table->set_header(0, get_lang('OfficialCode'), true);
    $tab_table_header[] = get_lang('OfficialCode');
    if ($is_western_name_order) {
        $table->set_header(1, get_lang('FirstName'), true);
        $tab_table_header[] = get_lang('FirstName');
        $table->set_header(2, get_lang('LastName'), true);
        $tab_table_header[] = get_lang('LastName');
    } else {
        $table->set_header(1, get_lang('LastName'), true);
        $tab_table_header[] = get_lang('LastName');
        $table->set_header(2, get_lang('FirstName'), true);
        $tab_table_header[] = get_lang('FirstName');
    }
    $table->set_header(3, get_lang('Login'), false);
    $tab_table_header[] = get_lang('Login');

    $table->set_header(4, get_lang('TrainingTime'), false);
    $tab_table_header[] = get_lang('TrainingTime');
    $table->set_header(5, get_lang('CourseProgress').'&nbsp;'.Display::return_icon('info3.gif', get_lang('ScormAndLPProgressTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $tab_table_header[] = get_lang('CourseProgress');

    $table->set_header(6, get_lang('ExerciseProgress'), false);
    $tab_table_header[] = get_lang('ExerciseProgress');
    $table->set_header(7, get_lang('ExerciseAverage'), false);
    $tab_table_header[] = get_lang('ExerciseAverage');
    $table->set_header(8, get_lang('Score').'&nbsp;'.Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
    $tab_table_header[] = get_lang('Score');
    $table->set_header(9, get_lang('Student_publication'), false);
    $tab_table_header[] = get_lang('Student_publication');
    $table->set_header(10, get_lang('Messages'), false);
    $tab_table_header[] = get_lang('Messages');

    if (empty($session_id)) {
        $table->set_header(11, get_lang('Survey'), false);
        $tab_table_header[] = get_lang('Survey');
        $table->set_header(12, get_lang('FirstLogin'), false);
        $tab_table_header[] = get_lang('FirstLogin');
        $table->set_header(13, get_lang('LatestLogin'), false);
        $tab_table_header[] = get_lang('LatestLogin');
        if (isset($_GET['additional_profile_field']) and is_numeric($_GET['additional_profile_field'])) {
            $table->set_header(14, $extra_info['field_display_text'], false);
            $tab_table_header[] = $extra_info['field_display_text'];
            $table->set_header(15, get_lang('Details'), false);
            $tab_table_header[] = get_lang('Details');
        } else {
            $table->set_header(14, get_lang('Details'), false);
            $tab_table_header[] = get_lang('Details');
        }

    } else {
        $table->set_header(11, get_lang('FirstLogin'), false);
        $tab_table_header[] = get_lang('FirstLogin');
        $table->set_header(12, get_lang('LatestLogin'), false);
        $tab_table_header[] = get_lang('LatestLogin');

        if (isset($_GET['additional_profile_field']) and is_numeric($_GET['additional_profile_field'])) {
            $table->set_header(13, $extra_info['field_display_text'], false);
            $tab_table_header[] = $extra_info['field_display_text'];
            $table->set_header(14, get_lang('Details'), false);
            $tab_table_header[] = get_lang('Details');
        } else {
            $table->set_header(13, get_lang('Details'), false);
            $tab_table_header[] = get_lang('Details');
        }
    }
    // display buttons to un hide hidden columns
    echo "<br/><br/><div id='unhideButtons'>";
    for ($i=0; $i < count($tab_table_header); $i++) {
        $index = $i + 1;
        echo "<span title='".get_lang('DisplayColumn')." ".$tab_table_header[$i]."' class='unhide_button hide' onclick='foldup($index)'>".Display :: return_icon('move.png', get_lang('DisplayColumn'), array('align'=>'absmiddle', 'hspace'=>'3px'), 16)." ".$tab_table_header[$i]."</span>";
    }
    echo "</div>";
    // Display the table
    echo "<div id='reporting_table'>";
    $table->display();
    echo "</div>";
} else {
    echo Display::display_warning_message(get_lang('NoUsersInCourse'));
}

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

    $csv_headers[] = get_lang('FirstLogin', '');
    $csv_headers[] = get_lang('LatestLogin', '');

    if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
        $csv_headers[] = $extra_info['field_display_text'];
    }
    ob_end_clean();
    array_unshift($csv_content, $csv_headers); // Adding headers before the content.

    Export::export_table_csv($csv_content, 'reporting_student_list');
    exit;
}
Display::display_footer();
