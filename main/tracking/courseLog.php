<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_TRACKING;

$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    api_not_allowed(true);
}
$sessionId = api_get_session_id();
$is_allowedToTrack = Tracking::isAllowToTrack($sessionId);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

//keep course_code form as it is loaded (global) by the table's get_user_data
$courseCode = $courseInfo['code'];
$courseId = $courseInfo['real_id'];

// PERSON_NAME_DATA_EXPORT is buggy
$sortByFirstName = api_sort_by_first_name();
$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;
$origin = api_get_origin();

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'] ? true : false;

$htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
$htmlHeadXtra[] = ' ';

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    $from_myspace = true;
    $this_section = 'session_my_space';
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
        if (!in_array($courseCode, $coursesFollowedList)) {
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
if ('resume_session' === $origin) {
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
    $studentList = CourseManager::get_student_list_from_course_code($courseCode);
} else {
    // Registered students in session.
    $studentList = CourseManager::get_student_list_from_course_code($courseCode, true, $sessionId);
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

$actionsRight .= '<a href="'.api_get_self().'?'.api_get_cidreq(
    ).'&export=csv&'.$additionalParams.$users_tracking_per_page.'">
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
$form_search->addHidden('cidReq', $courseCode);
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
                if (SESSION_INVISIBLE == $visibility) {
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
$hideReports = api_get_configuration_value('hide_course_report_graph');
$conditions = [];

$groupList = GroupManager::get_group_list(null, $courseInfo, 1, $sessionId);

$class = new UserGroup();
//$options['where'] = [' usergroup.course_id = ? ' => $courseId];
//$classes = $class->getUserGroupInCourse($options);
$classes = $class->get_all();

// Show the charts part only if there are students subscribed to this course/session
if ($nbStudents > 0) {
    // Classes
    $formClass = new FormValidator(
        'classes',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $formClass->addHidden('cidReq', $courseCode);
    $formClass->addHidden('id_session', $sessionId);
    $groupIdList = ['--'];
    $select = $formClass->addSelect('class_id', get_lang('Class').'/'.get_lang('Group'), $groupIdList);
    $groupIdList = [];
    foreach ($classes as $class) {
        //$groupIdList['class_'.$class['id']] = $class['name'];
        $groupIdList[] = ['text' => $class['name'], 'value' => 'class_'.$class['id']];
    }
    $select->addOptGroup($groupIdList, get_lang('Class'));
    $groupIdList = [];
    foreach ($groupList as $group) {
        $groupIdList[] = ['text' => $group['name'], 'value' => 'group_'.$group['id']];
    }
    $select->addOptGroup($groupIdList, get_lang('Group'));
    $formClass->addButtonSearch(get_lang('Search'));

    // Groups
    /*$formGroup = new FormValidator(
        'groups',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $formGroup->addHidden('cidReq', $courseCode);
    $formGroup->addHidden('id_session', $sessionId);
    $groupIdList = ['--'];
    foreach ($groupList as $group) {
        $groupIdList[$group['id']] = $group['name'];
    }
    $formGroup->addSelect('group_id', get_lang('Group'), $groupIdList);
    $formGroup->addButtonSearch(get_lang('Search'));*/

    // Extra fields
    $formExtraField = new FormValidator(
        'extra_fields',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $formExtraField->addHidden('cidReq', $courseCode);
    $formExtraField->addHidden('id_session', $sessionId);
    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $fieldId = Security::remove_XSS($fieldId);
            $formExtraField->addHidden('additional_profile_field[]', $fieldId);
            //$formGroup->addHidden('additional_profile_field[]', $fieldId);
            $formClass->addHidden('additional_profile_field[]', $fieldId);
        }
    }

    $extraField = new ExtraField('user');
    $extraField->addElements($formExtraField, 0, [], true);
    $formExtraField->addButtonSearch(get_lang('Search'));

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
        $courseCode,
        null,
        null,
        $sessionId
    );

    $conditions = [];
    $fields = [];

    if ($formClass->validate()) {
        $classId = null;
        $groupId = null;

        $part = $formClass->getSubmitValue('class_id');
        $item = explode('_', $part);
        if (isset($item[0]) && isset($item[1])) {
            if ('class' === $item[0]) {
                $classId = (int) $item[1];
            } else {
                $groupId = (int) $item[1];
            }
        }

        if (!empty($classId)) {
            $whereCondition = " AND gu.usergroup_id = $classId ";
            $tableGroup = Database::get_main_table(TABLE_USERGROUP_REL_USER);
            $joins = " INNER JOIN $tableGroup gu ON (user.id = gu.user_id) ";
            $conditions = ['where' => $whereCondition, 'inject_joins' => $joins];
        }

        if (!empty($groupId)) {
            $whereCondition = " AND gu.group_id = $groupId ";
            $tableGroup = Database::get_course_table(TABLE_GROUP_USER);
            $joins = " INNER JOIN $tableGroup gu ON (user.id = gu.user_id) ";
            $conditions = ['where' => $whereCondition, 'inject_joins' => $joins];
        }
    }

    /*if ($formGroup->validate()) {
        $groupId = (int) $formGroup->getSubmitValue('group_id');
        if (!empty($groupId)) {
            $whereCondition = " AND gu.group_id = $groupId ";
            $tableGroup = Database::get_course_table(TABLE_GROUP_USER);
            $joins = " INNER JOIN $tableGroup gu ON (user.id = gu.user_id) ";
            $conditions = ['where' => $whereCondition, 'inject_joins' => $joins];
        }
    }*/

    if ($formExtraField->validate()) {
        $extraResult = $extraField->processExtraFieldSearch($_REQUEST, $formExtraField, 'user');
        if (!empty($extraResult)) {
            $conditions = $extraResult['condition'];
            $fields = $extraResult['fields'];
        }
    }

    if ($hideReports === false) {
        $conditions['include_invited_users'] = false;
        $usersTracking = TrackingCourseLog::get_user_data(
            null,
            $nbStudents,
            $trackingColumn,
            $trackingDirection,
            $conditions
        );
        foreach ($usersTracking as $userTracking) {
            $userInfo = api_get_user_info_from_username($userTracking[3]);
            if (empty($userInfo)) {
                continue;
            }
            $userId = $userInfo['user_id'];
            if ('100%' === $userTracking[5]) {
                $numberStudentsCompletedLP++;
            }
            $averageStudentTestScore = substr($userTracking[7], 0, -1);
            $averageStudentsTestScore += $averageStudentTestScore;

            if ('100' === $averageStudentTestScore) {
                $reducedAverage = 9;
            } else {
                $reducedAverage = floor($averageStudentTestScore / 10);
            }
            if (isset($scoresDistribution[$reducedAverage])) {
                $scoresDistribution[$reducedAverage]++;
            }
            $scoreStudent = substr($userTracking[5], 0, -1) + substr($userTracking[7], 0, -1);
            [$hours, $minutes, $seconds] = preg_split('/:/', $userTracking[4]);
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

        echo $tpl->fetch($tpl->get_template('tracking/tracking_course_log.tpl'));
    }
}

$html .= Display::page_subheader2(get_lang('StudentList'));

$bestScoreLabel = get_lang('Score').' - '.get_lang('BestAttempt');
if ($nbStudents > 0) {
    $mainForm = new FormValidator(
        'filter',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $mainForm->addButtonAdvancedSettings(
        'advanced_search',
        [get_lang('AdvancedSearch')]
    );
    $mainForm->addHtml('<div id="advanced_search_options" style="display:none;">');
    $mainForm->addHtml($formClass->returnForm());
    $mainForm->addHtml($formExtraField->returnForm());
    $mainForm->addHtml('</div>');
    $html .= $mainForm->returnForm();

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

    $extraFieldSelect = TrackingCourseLog::display_additional_profile_fields();
    if (!empty($extraFieldSelect)) {
        $html .= $extraFieldSelect;
    }

    $html .= $form->returnForm();

    if ($export_csv) {
        $csv_content = [];
        //override the SortableTable "per page" limit if CSV
        $_GET['users_tracking_per_page'] = 1000000;
    }

    if ($hideReports === false) {
        $table = new SortableTableFromArray(
            $usersTracking,
            1,
            20,
            'users_tracking'
        );
        $table->total_number_of_items = $nbStudents;
    } else {
        $conditions['include_invited_users'] = true;
        $table = new SortableTable(
            'users_tracking',
            ['TrackingCourseLog', 'get_number_of_users'],
            ['TrackingCourseLog', 'get_user_data'],
            1,
            20
        );
        $table->setDataFunctionParams($conditions);
    }

    $parameters['cidReq'] = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
    $parameters['id_session'] = $sessionId;
    $parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

    $headerCounter = 0;
    $headers = [];
    // tab of header texts
    $table->set_header($headerCounter++, get_lang('OfficialCode'), true);
    $headers['official_code'] = get_lang('OfficialCode');
    if ($sortByFirstName) {
        $table->set_header($headerCounter++, get_lang('FirstName'), true);
        $table->set_header($headerCounter++, get_lang('LastName'), true);
        $headers['firstname'] = get_lang('FirstName');
        $headers['lastname'] = get_lang('LastName');
    } else {
        $table->set_header($headerCounter++, get_lang('LastName'), true);
        $table->set_header($headerCounter++, get_lang('FirstName'), true);
        $headers['lastname'] = get_lang('LastName');
        $headers['firstname'] = get_lang('FirstName');
    }
    $table->set_header($headerCounter++, get_lang('Login'), false);
    $headers['login'] = get_lang('Login');

    $table->set_header(
        $headerCounter++,
        get_lang('TrainingTime').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('CourseTimeInfo'), [], ICON_SIZE_TINY),
        false
    );
    $headers['training_time'] = get_lang('TrainingTime');
    $table->set_header(
        $headerCounter++,
        get_lang('CourseProgress').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ScormAndLPProgressTotalAverage'), [], ICON_SIZE_TINY),
        false
    );
    $headers['course_progress'] = get_lang('CourseProgress');

    $table->set_header(
        $headerCounter++,
        get_lang('ExerciseProgress').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ExerciseProgressInfo'), [], ICON_SIZE_TINY),
        false
    );
    $headers['exercise_progress'] = get_lang('ExerciseProgress');
    $table->set_header(
        $headerCounter++,
        get_lang('ExerciseAverage').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ExerciseAverageInfo'), [], ICON_SIZE_TINY),
        false
    );
    $headers['exercise_average'] = get_lang('ExerciseAverage');

    $table->set_header(
        $headerCounter++,
        get_lang('Score').'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), [], ICON_SIZE_TINY),
        false
    );
    $headers['score'] = get_lang('Score');

    $table->set_header(
        $headerCounter++,
        $bestScoreLabel.'&nbsp;'.
        Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), [], ICON_SIZE_TINY),
        false
    );
    $headers['score_best'] = $bestScoreLabel;

    $addExerciseOption = api_get_configuration_value('add_exercise_best_attempt_in_report');
    $exerciseResultHeaders = [];
    if (!empty($addExerciseOption) && isset($addExerciseOption['courses']) &&
        isset($addExerciseOption['courses'][$courseCode])
    ) {
        foreach ($addExerciseOption['courses'][$courseCode] as $exerciseId) {
            $exercise = new Exercise();
            $exercise->read($exerciseId);
            if ($exercise->iId) {
                $title = get_lang('Exercise').': '.$exercise->get_formated_title();
                $table->set_header(
                    $headerCounter++,
                    $title,
                    false
                );
                $exerciseResultHeaders[] = $title;
                $headers['exercise_'.$exercise->iId] = $title;
            }
        }
    }

    $table->set_header($headerCounter++, get_lang('Student_publication'), false);
    $headers['student_publication'] = get_lang('Student_publication');
    $table->set_header($headerCounter++, get_lang('Messages'), false);
    $headers['messages'] = get_lang('Messages');
    $table->set_header($headerCounter++, get_lang('Classes'));
    $headers['classes'] = get_lang('Classes');

    if (empty($sessionId)) {
        $table->set_header($headerCounter++, get_lang('Survey'), false);
        $headers['survey'] = get_lang('Survey');
    } else {
        $table->set_header($headerCounter++, get_lang('RegisteredDate'), false);
        $headers['registered_at'] = get_lang('RegisteredDate');
    }
    $table->set_header($headerCounter++, get_lang('FirstLoginInCourse'), false);
    $headers['first_login'] = get_lang('FirstLoginInCourse');
    $table->set_header($headerCounter++, get_lang('LatestLoginInCourse'), false);
    $headers['latest_login'] = get_lang('LatestLoginInCourse');

    $counter = $headerCounter;
    if (api_get_setting('show_email_addresses') === 'true') {
        $table->set_header($counter, get_lang('Email'), false);
        $headers['email'] = get_lang('Email');
        $counter++;
    }
    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $table->set_header($counter, $extra_info[$fieldId]['display_text'], false);
            $headers[$extra_info[$fieldId]['variable']] = $extra_info[$fieldId]['display_text'];
            $counter++;
            $parameters['additional_profile_field'] = $fieldId;
        }
    }
    $table->set_header($counter, get_lang('Details'), false);
    $headers['Details'] = get_lang('Details');

    if (!empty($fields)) {
        foreach ($fields as $key => $value) {
            $key = Security::remove_XSS($key);
            $value = Security::remove_XSS($value);
            $parameters[$key] = $value;
        }
    }
    $parameters['cidReq'] = $courseCode;
    $parameters['id_session'] = $sessionId;
    $table->set_additional_parameters($parameters);
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

$groupContent = '';
echo Display::panel($html, $titleSession);

$groupTable = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table']);
$column = 0;
$groupTable->setHeaderContents(0, $column++, get_lang('Name'));
$groupTable->setHeaderContents(0, $column++, get_lang('TrainingTime'));
$groupTable->setHeaderContents(0, $column++, get_lang('AverageTrainingTime'));
$groupTable->setHeaderContents(0, $column++, get_lang('CourseProgress'));
$groupTable->setHeaderContents(0, $column++, get_lang('ExerciseAverage'));

$exerciseList = ExerciseLib::get_all_exercises(
    $courseInfo,
    $sessionId,
    false,
    null,
    false,
    3
);

if (!empty($groupList)) {
    $totalTime = null;
    $totalLpProgress = null;
    $totalScore = null;
    $totalAverageTime = null;
    $totalBestScoreAverageNotInLP = 0;
    $row = 1;
    foreach ($groupList as $groupInfo) {
        $column = 0;
        $groupTable->setCellContents($row, $column++, $groupInfo['name']);
        $usersInGroup = GroupManager::getStudents($groupInfo['iid']);

        $time = null;
        $lpProgress = null;
        $score = null;
        $averageTime = null;
        $bestScoreAverageNotInLP = null;
        if (!empty($usersInGroup)) {
            $usersInGroup = array_column($usersInGroup, 'user_id');
            $userInGroupCount = count($usersInGroup);
            $timeInSeconds = Tracking::get_time_spent_on_the_course(
                $usersInGroup,
                $courseId,
                $sessionId
            );
            $totalTime += $timeInSeconds;
            if (!empty($timeInSeconds)) {
                $time = api_time_to_hms($timeInSeconds);
                $averageTime = $timeInSeconds / $userInGroupCount;
                $totalAverageTime += $averageTime;
                $averageTime = api_time_to_hms($averageTime);
            }

            $totalGroupLpProgress = 0;
            foreach ($usersInGroup as $studentId) {
                $lpProgress = Tracking::get_avg_student_progress(
                    $usersInGroup,
                    $courseCode,
                    [],
                    $sessionId
                );
                $totalGroupLpProgress += $lpProgress;
            }

            if (empty($totalGroupLpProgress)) {
                $totalGroupLpProgress = '';
            } else {
                $lpProgress = $totalGroupLpProgress / $userInGroupCount;
                $totalLpProgress += $totalGroupLpProgress;
            }

            if (!empty($exerciseList)) {
                foreach ($exerciseList as $exerciseData) {
                    foreach ($usersInGroup as $userId) {
                        $results = Event::get_best_exercise_results_by_user(
                            $exerciseData['id'],
                            $courseInfo['real_id'],
                            0,
                            $userId
                        );
                        $best = 0;
                        if (!empty($results)) {
                            foreach ($results as $result) {
                                if (!empty($result['exe_weighting'])) {
                                    $score = $result['exe_result'] / $result['exe_weighting'];
                                    if ($score > $best) {
                                        $best = $score;
                                    }
                                }
                            }
                        }
                        $bestScoreAverageNotInLP += $best;
                    }
                }
                $bestScoreAverageNotInLP = round(
                    $bestScoreAverageNotInLP / count($exerciseList) * 100 / $userInGroupCount,
                    2
                );

                $totalBestScoreAverageNotInLP += $bestScoreAverageNotInLP;
            }

            if (empty($score)) {
                $score = '';
            }
            if (empty($lpProgress)) {
                $lpProgress = '';
            }
            if (empty($bestScoreAverageNotInLP)) {
                $bestScoreAverageNotInLP = '';
            }
        }

        $groupTable->setCellContents($row, $column++, $time);
        $groupTable->setCellContents($row, $column++, $averageTime);
        $groupTable->setCellContents($row, $column++, $lpProgress);
        $groupTable->setCellContents($row, $column++, $bestScoreAverageNotInLP);
        $row++;
    }

    $column = 0;
    $totalTime = api_time_to_hms($totalTime);
    $totalAverageTime = api_time_to_hms($totalAverageTime);
    $groupTable->setCellContents($row, $column++, get_lang('Total'));
    $groupTable->setCellContents($row, $column++, $totalTime);
    $groupTable->setCellContents($row, $column++, $totalAverageTime);
    $groupTable->setCellContents($row, $column++, round($totalLpProgress / count($groupList), 2).'% ');
    $groupTable->setCellContents($row, $column++, round($totalBestScoreAverageNotInLP / count($groupList), 2).'% ');
} else {
    $userIdList = Session::read('user_id_list');

    if (!empty($userIdList)) {
        $studentIdList = $userIdList;
    } else {
        $studentIdList = array_column($studentList, 'user_id');
    }
    $nbStudents = count($studentIdList);

    $timeInSeconds = Tracking::get_time_spent_on_the_course(
        $studentIdList,
        $courseId,
        $sessionId
    );
    $averageTime = null;
    if (!empty($timeInSeconds)) {
        $time = api_time_to_hms($timeInSeconds);
        $averageTime = $timeInSeconds / $nbStudents;
        $averageTime = api_time_to_hms($averageTime);
    }
    $totalLpProgress = 0;
    foreach ($studentIdList as $studentId) {
        $lpProgress = Tracking::get_avg_student_progress(
            $studentId,
            $courseCode,
            [],
            $sessionId
        );
        $totalLpProgress += $lpProgress;
    }

    if (empty($nbStudents)) {
        $lpProgress = '0 %';
    } else {
        $lpProgress = round($totalLpProgress / $nbStudents, 2).' %';
    }
    $totalBestScoreAverageNotInLP = 0;
    $bestScoreAverageNotInLP = 0;
    if (!empty($exerciseList)) {
        foreach ($exerciseList as $exerciseData) {
            foreach ($studentIdList as $userId) {
                $results = Event::get_best_exercise_results_by_user(
                    $exerciseData['id'],
                    $courseInfo['real_id'],
                    $sessionId,
                    $userId
                );
                $best = 0;
                if (!empty($results)) {
                    foreach ($results as $result) {
                        if (!empty($result['exe_weighting'])) {
                            $score = $result['exe_result'] / $result['exe_weighting'];
                            if ($score > $best) {
                                $best = $score;
                            }
                        }
                    }
                }

                $bestScoreAverageNotInLP += $best;
            }
        }

        if (!empty($nbStudents)) {
            $bestScoreAverageNotInLP = round(
                    $bestScoreAverageNotInLP / count($exerciseList) * 100 / $nbStudents,
                    2
                ).' %';
        }
    }

    $row = 1;
    $column = 0;
    $groupTable->setCellContents($row, $column++, get_lang('Total'));
    $groupTable->setCellContents($row, $column++, $time);
    $groupTable->setCellContents($row, $column++, $averageTime);
    $groupTable->setCellContents($row, $column++, $lpProgress);
    $groupTable->setCellContents($row, $column++, $bestScoreAverageNotInLP);
}

echo Display::panel($groupTable->toHtml(), '');

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
    $csv_headers[] = $bestScoreLabel;
    if (!empty($exerciseResultHeaders)) {
        foreach ($exerciseResultHeaders as $exerciseLabel) {
            $csv_headers[] = $exerciseLabel;
        }
    }
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
