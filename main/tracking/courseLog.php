<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_TRACKING;

TrackingCourseLog::protectIfNotAllowed();

$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();

//keep course_code form as it is loaded (global) by the table's get_user_data
$courseCode = $courseInfo['code'];
$courseId = $courseInfo['real_id'];
$parameters['cidReq'] = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$parameters['id_session'] = $sessionId;
$parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;
$parameters['user_active'] = isset($_REQUEST['user_active']) && is_numeric($_REQUEST['user_active']) ? (int) $_REQUEST['user_active'] : null;

// PERSON_NAME_DATA_EXPORT is buggy
$sortByFirstName = api_sort_by_first_name();
$from_myspace = false;
$from = $_GET['from'] ?? null;
$origin = api_get_origin();
$lpShowMaxProgress = api_get_configuration_value('lp_show_max_progress_instead_of_average');
if (api_get_configuration_value('lp_show_max_progress_or_average_enable_course_level_redefinition')) {
    $lpShowProgressCourseSetting = api_get_course_setting('lp_show_max_or_average_progress');
    if (in_array($lpShowProgressCourseSetting, ['max', 'average'])) {
        $lpShowMaxProgress = ('max' === $lpShowProgressCourseSetting);
    }
}

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];

$htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
$htmlHeadXtra[] = ' ';

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    $from_myspace = true;
    $this_section = 'session_my_space';
}

$additionalParams = '';
if (isset($_GET['additional_profile_field'])) {
    foreach ($_GET['additional_profile_field'] as $fieldId) {
        $additionalParams .= '&additional_profile_field[]='.(int) $fieldId;
    }
}

if (isset($parameters['user_active'])) {
    $additionalParams .= '&user_active='.(int) $parameters['user_active'];
}

if ($export_csv || isset($_GET['csv'])) {
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

$visibleIcon = Display::return_icon(
    'visible.png',
    get_lang('HideColumn'),
    ['align' => 'absmiddle', 'hspace' => '3px']
);

$exportInactiveUsers = api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq().'&'.$additionalParams;

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
                    '<div style=\"cursor:pointer\" onclick=\"foldup(' + index + ')\">".$visibleIcon."</div>'
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
        $('#download-csv').on('click', function (e) {
            e.preventDefault();
            location.href = '".$exportInactiveUsers.'&csv=1&since='."'+$('#reminder_form_since').val();
        });
    })
</script>";
$htmlHeadXtra[] = $js;

// Database table definitions.
//@todo remove this calls
$TABLETRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);

$userEditionExtraFieldToCheck = api_get_configuration_value('user_edition_extra_field_to_check');

$objExtrafieldUser = new ExtraField('user');

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

$view = $_REQUEST['view'] ?? '';
$nameTools = get_lang('Tracking');

$tpl = new Template($nameTools);
// Getting all the students of the course
if (empty($sessionId)) {
    // Registered students in a course outside session.
    $studentList = CourseManager::get_student_list_from_course_code(
        $courseCode,
        false,
        0,
        null,
        null,
        true,
        0,
        false,
        0,
        0,
        $parameters['user_active']
    );
} else {
    // Registered students in session.
    $studentList = CourseManager::get_student_list_from_course_code(
        $courseCode,
        true,
        $sessionId,
        null,
        null,
        true,
        0,
        false,
        0,
        0,
        $parameters['user_active']
    );
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
        $extra_info[$fieldId] = $objExtrafieldUser->getFieldInfoByFieldId($fieldId);
    }
}

Session::write('additional_user_profile_info', $userProfileInfo);
Session::write('extra_field_info', $extra_info);

$defaultExtraFields = [];
$defaultExtraFieldsFromSettings = [];
$defaultExtraFieldsFromSettings = api_get_configuration_value('course_log_default_extra_fields');
if (!empty($defaultExtraFieldsFromSettings) && isset($defaultExtraFieldsFromSettings['extra_fields'])) {
    $defaultExtraFields = $defaultExtraFieldsFromSettings['extra_fields'];
    $defaultExtraInfo = [];
    $defaultUserProfileInfo = [];

    foreach ($defaultExtraFields as $fieldName) {
        $extraFieldInfo = UserManager::get_extra_field_information_by_name($fieldName);

        if (!empty($extraFieldInfo)) {
            // Fetching only the user that are loaded NOT ALL user in the portal.
            $defaultUserProfileInfo[$extraFieldInfo['id']] = TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser(
                $extraFieldInfo['id'],
                $user_ids
            );
            $defaultExtraInfo[$extraFieldInfo['id']] = $extraFieldInfo;
        }
    }

    Session::write('default_additional_user_profile_info', $defaultUserProfileInfo);
    Session::write('default_extra_field_info', $defaultExtraInfo);
}

Display::display_header($nameTools, 'Tracking');

$actionsLeft = TrackingCourseLog::actionsLeft('users', $sessionId);

$actionsRight = '<div class="pull-right">';
$actionsRight .= '<a href="javascript: void(0);" onclick="window.print();">'.
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page = '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}

$actionsRight .= '<a
    href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$additionalParams.$users_tracking_per_page.'">
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
$renderer = $form_search->defaultRenderer();
$form_search->addHidden('from', Security::remove_XSS($from));
$form_search->addHidden('session_id', $sessionId);
$form_search->addHidden('id_session', $sessionId);
$form_search->addHidden('cidReq', $courseCode);
$form_search->addElement('text', 'user_keyword');
$form_search->addButtonSearch(get_lang('SearchUsers'));
echo Display::toolbarAction(
    'toolbar-courselog',
    [$actionsLeft, $form_search->returnForm(), $actionsRight],
    [4, 6, 2]
);
echo Display::page_header(
    Display::return_icon('course.png', get_lang('Course')).PHP_EOL
        .$courseInfo['name'],
    $sessionId
        ? Display::return_icon('session.png', get_lang('Session')).PHP_EOL
            .Security::remove_XSS(api_get_session_name($sessionId))
        : null
);

$html = TrackingCourseLog::getTeachersOrCoachesHtmlHeader(
    $courseInfo['code'],
    $courseInfo['real_id'],
    $sessionId,
    true
);

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
            $html .= Display::tag('li', $icon.' '.Display::url(Security::remove_XSS($session['name']), $url));
        }
        $html .= '</ul>';
    }
}

$trackingColumn = $_GET['users_tracking_column'] ?? null;
$trackingDirection = $_GET['users_tracking_direction'] ?? null;
$hideReports = (int) api_get_configuration_value('hide_course_report_graph');

$groupList = GroupManager::get_group_list(null, $courseInfo, 1, $sessionId);

$class = new UserGroup();
$classes = $class->get_all();

$bestScoreLabel = get_lang('Score').' - '.get_lang('BestAttempt');

// Show the charts part only if there are students subscribed to this course/session
if ($nbStudents > 0 || isset($parameters['user_active'])) {
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
        $groupIdList[] = ['text' => $class['name'], 'value' => 'class_'.$class['id']];
    }
    $select->addOptGroup($groupIdList, get_lang('Class'));
    $groupIdList = [];
    foreach ($groupList as $group) {
        $groupIdList[] = ['text' => $group['name'], 'value' => 'group_'.$group['id']];
    }
    $select->addOptGroup($groupIdList, get_lang('Group'));
    $formClass->addButtonSearch(get_lang('Search'));

    // Filter by ex learners
    if (false !== $userEditionExtraFieldToCheck) {
        $formExLearners = new FormValidator(
            'form_exlearners',
            'get',
            api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
        );
        $group = [];
        $group[] = $formExLearners->createElement('radio', 'opt_exlearner', 'id="opt_exlearner1"', get_lang('Yes'), 1);
        $group[] = $formExLearners->createElement('radio', 'opt_exlearner', 'id="opt_exlearner0"', get_lang('No'), 0);
        $formExLearners->addGroup($group, 'exlearner', get_lang('ToHideExlearners'));
        $formExLearners->addHidden('cidReq', $courseCode);
        $formExLearners->addHidden('id_session', $sessionId);
        $formExLearners->addButtonSearch(get_lang('Search'));
    }

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

    if (isset($_GET['additional_profile_field'])) {
        // Extra fields
        $formExtraField = new FormValidator(
            'extra_fields',
            'get',
            api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
        );
        $formExtraField->addHidden('cidReq', $courseCode);
        $formExtraField->addHidden('id_session', $sessionId);

        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $fieldId = Security::remove_XSS($fieldId);
            $formExtraField->addHidden('additional_profile_field[]', $fieldId);
            $formClass->addHidden('additional_profile_field[]', $fieldId);
        }

        $objExtrafieldUser->addElements($formExtraField, 0, [], true);
        $formExtraField->addButtonSearch(get_lang('Search'));
    }

    // Filter by active users
    $formActiveUsers = new FormValidator(
        'active_users',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    // Filter by active users
    $group = [];
    $group[] = $formActiveUsers->createElement('radio', 'user_active', 'id="user_active1"', get_lang('Yes'), 1);
    $group[] = $formActiveUsers->createElement('radio', 'user_active', 'id="user_active0"', get_lang('No'), 0);
    $formActiveUsers->addGroup($group, '', get_lang('AccountActive'));
    $formActiveUsers->addButtonSearch(get_lang('Search'));

    if (isset($parameters['user_active'])) {
        $formActiveUsers->setDefaults(['user_active' => $parameters['user_active']]);
    }

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
    if (false !== $userEditionExtraFieldToCheck) {
        if ($formExLearners->validate()) {
            $formValue = $formExLearners->getSubmitValue('exlearner');
            if (isset($formValue['opt_exlearner']) && 1 == $formValue['opt_exlearner']) {
                $sessionId = api_get_session_id();
                if (!empty($sessionId)) {
                    $tableSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                    $joins = " INNER JOIN $tableSessionCourseUser scu ON scu.user_id = user.id ";
                    $whereCondition = " AND scu.status !=  ".COURSE_EXLEARNER." AND scu.c_id = '".api_get_course_int_id()."' AND scu.session_id = $sessionId";
                } else {
                    $tableCourseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
                    $joins = " INNER JOIN $tableCourseUser cu ON cu.user_id = user.id ";
                    $whereCondition = " AND cu.relation_type !=  ".COURSE_EXLEARNER." AND cu.c_id = '".api_get_course_int_id()."'";
                }
                $conditions = ['where' => $whereCondition, 'inject_joins' => $joins];
            }
        }
    }

    if ($formActiveUsers->validate()) {
        $userActive = $formActiveUsers->getSubmitValue('user_active');
        if (isset($userActive) && is_numeric($userActive)) {
            $active = (int) $userActive;
            $whereCondition = " AND user.active = $active ";
            $conditions = ['where' => $whereCondition, 'inject_joins' => ''];
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

    if (isset($formExtraField) && $formExtraField->validate()) {
        $extraResult = $objExtrafieldUser->processExtraFieldSearch($_REQUEST, $formExtraField, 'user');
        if (!empty($extraResult)) {
            $conditions = $extraResult['condition'];
            $fields = $extraResult['fields'];
        }
    }

    if (TrackingCourseLog::HIDE_COURSE_REPORT_GRAPH_HIDDEN !== $hideReports) {
        Session::write(
            'course_log_args',
            [
                'parameters' => $parameters,
                'conditions' => $conditions,
            ]
        );

        echo '<script>
            $(function () {
                function loadGraphs () {
                    $("#tracking-course-summary-wrapper .skeleton").addClass("skeleton--loading");
                    $("#tracking-course-summary-wrapper")
                        .load(_p.web_ajax + "course_log.ajax.php?a=graph&'.api_get_cidreq().'");
                }
        ';

        if (TrackingCourseLog::HIDE_COURSE_REPORT_GRAPH_SHOWN === $hideReports) {
            echo 'loadGraphs();';
        }

        if (TrackingCourseLog::HIDE_COURSE_REPORT_GRAPH_CLICK_SHOW === $hideReports) {
            echo '$("#tracking-course-summary-loader").click(function () {
                    $(this).remove();
                    loadGraphs();
                });
            ';
        }

        echo '});
            </script>
            <style>
                #tracking-course-summary-wrapper {
                    position: relative;
                    z-index: 998;
                }
                #tracking-course-summary-loader {
                    position: absolute;
                    left: 50%;
                    top: 50%;
                    -webkit-transform: translate(-50%, -50%);
                    -moz-transform: translate(-50%, -50%);
                    -o-transform: translate(-50%, -50%);
                    transform: translate(-50%, -50%);
                    z-index: 999;
                }
            </style>
            <div id="tracking-course-summary-wrapper">
                <div class="row">
                    <div class="col-lg-3 col-sm-3">
                        <div class="skeleton" style="height: 82px;"></div>
                    </div>
                    <div class="col-lg-3 col-sm-3">
                        <div class="skeleton" style="height: 82px;"></div>
                    </div>
                    <div class="col-lg-3 col-sm-3">
                        <div class="skeleton" style="height: 82px;"></div>
                    </div>
                    <div class="col-lg-3 col-sm-3">
                        <div class="skeleton" style="height: 82px;"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="skeleton" style="height: 241px;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton" style="height: 241px;"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="skeleton" style="height: 241px;"></div>
                    </div>
                </div>
        ';

        if (TrackingCourseLog::HIDE_COURSE_REPORT_GRAPH_CLICK_SHOW === $hideReports) {
            echo '<button type="button" class="btn btn-info" id="tracking-course-summary-loader">'
                .Display::returnFontAwesomeIcon('bar-chart', '', true)
                .get_lang('ClickToShowGraphs')
                .'</button>
            ';
        }

        echo '</div>';
    }

    $html .= Display::page_subheader2(get_lang('StudentList'));

    $mainForm = new FormValidator(
        'filter',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $mainForm->addButtonAdvancedSettings(
        'advanced_search',
        [get_lang('AdvancedSearch')]
    );
    $html .= $mainForm->returnForm();

    $html .= '<div id="advanced_search_options" style="display:none;">';
    $html .= $formClass->returnForm();
    $html .= isset($formExtraField) ? $formExtraField->returnForm() : '';
    $html .= false !== $userEditionExtraFieldToCheck ? $formExLearners->returnForm() : '';
    $html .= $formActiveUsers->returnForm();
    $html .= '</div>';
    $html .= '<hr>';

    $getLangXDays = get_lang('XDays');
    $form = new FormValidator(
        'reminder_form',
        'get',
        api_get_path(WEB_CODE_PATH).'announcements/announcements.php?'.api_get_cidreq(),
        null,
        ['style' => 'margin-bottom: 10px']
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
    $form->addLabel(get_lang('Export'), '<a id="download-csv" href="#!" class=" btn btn-default " > '.
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '').
        get_lang('ExportAsCSV')
    .' </a>');
    $extraFieldSelect = TrackingCourseLog::displayAdditionalProfileFields($defaultExtraFields);
    if (!empty($extraFieldSelect)) {
        $html .= $extraFieldSelect;
    }

    $html .= $form->returnForm();

    if ($export_csv) {
        //override the SortableTable "per page" limit if CSV
        $_GET['users_tracking_per_page'] = 1000000;
    }

    $conditions['include_invited_users'] = true;
    $table = new SortableTable(
        'users_tracking',
        ['TrackingCourseLog', 'getNumberOfUsers'],
        ['TrackingCourseLog', 'getUserData'],
        1,
        20
    );
    $table->setDataFunctionParams($conditions);

    $headerCounter = 0;
    $headers = [];
    // tab of header texts
    $table->set_header($headerCounter++, get_lang('OfficialCode'));
    $headers['official_code'] = get_lang('OfficialCode');
    if ($sortByFirstName) {
        $table->set_header($headerCounter++, get_lang('FirstName'));
        $table->set_header($headerCounter++, get_lang('LastName'));
        $headers['firstname'] = get_lang('FirstName');
        $headers['lastname'] = get_lang('LastName');
    } else {
        $table->set_header($headerCounter++, get_lang('LastName'));
        $table->set_header($headerCounter++, get_lang('FirstName'));
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

    $courseProgressHeadTitle = ($lpShowMaxProgress ? get_lang('ScormAndLPMaxProgress') : get_lang('ScormAndLPProgressTotalAverage'));
    $table->set_header(
        $headerCounter++,
        get_lang('CourseProgress').'&nbsp;'.
        Display::return_icon('info3.gif', $courseProgressHeadTitle, [], ICON_SIZE_TINY),
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
            if ($exercise->iid) {
                $title = get_lang('Exercise').': '.$exercise->get_formated_title();
                $table->set_header(
                    $headerCounter++,
                    $title,
                    false
                );
                $exerciseResultHeaders[] = $title;
                $headers['exercise_'.$exercise->iid] = $title;
            }
        }
    }

    $table->set_header($headerCounter++, get_lang('Student_publication'), false);
    $headers['student_publication'] = get_lang('Student_publication');
    $table->set_header($headerCounter++, get_lang('Messages'), false);
    $headers['messages'] = get_lang('Messages');
    $table->set_header($headerCounter++, get_lang('Classes'), false);
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

    $table->set_header($headerCounter++, get_lang('LpFinalizationDate'), false);
    $headers['lp_finalization_date'] = get_lang('LpFinalizationDate');

    $table->set_header($headerCounter++, get_lang('QuizFinalizationDate'), false);
    $headers['quiz_finalization_date'] = get_lang('QuizFinalizationDate');

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
    if (isset($defaultExtraFields)) {
        if (!empty($defaultExtraInfo)) {
            foreach ($defaultExtraInfo as $field) {
                $table->set_header($counter, $field['display_text'], false);
                $headers[$field['variable']] = $field['display_text'];
                $counter++;
            }
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
    $html .= Display::return_message(get_lang('NoUsersInCourse'), 'warning');
}

$groupContent = '';
echo Display::panel($html);

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

            $bestScoreAverageNotInLP = TrackingCourseLog::calcBestScoreAverageNotInLP(
                $exerciseList,
                $usersInGroup,
                (int) $courseInfo['real_id']
            );

            $totalBestScoreAverageNotInLP += $bestScoreAverageNotInLP;
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
    $time = api_time_to_hms($timeInSeconds);
    if (empty($nbStudents)) {
        $averageTime = api_time_to_hms($timeInSeconds);
    } else {
        $averageTime = api_time_to_hms($timeInSeconds / $nbStudents);
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
    $bestScoreAverageNotInLP = (string) TrackingCourseLog::calcBestScoreAverageNotInLP(
        $exerciseList,
        $studentIdList,
        (int) $courseInfo['real_id'],
        $sessionId,
        true
    );

    $row = 1;
    $column = 0;
    $groupTable->setCellContents($row, $column++, get_lang('Total'));
    $groupTable->setCellContents($row, $column++, $time);
    $groupTable->setCellContents($row, $column++, $averageTime);
    $groupTable->setCellContents($row, $column++, $lpProgress);
    $groupTable->setCellContents($row, $column++, $bestScoreAverageNotInLP);
}

echo Display::panel($groupTable->toHtml());

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
    $csv_headers[] = get_lang('Classes');

    if (empty($sessionId)) {
        $csv_headers[] = get_lang('Survey');
    } else {
        $csv_headers[] = get_lang('RegistrationDate');
    }

    $csv_headers[] = get_lang('FirstLoginInCourse');
    $csv_headers[] = get_lang('LatestLoginInCourse');
    $csv_headers[] = get_lang('LpFinalizationDate');
    $csv_headers[] = get_lang('QuizFinalizationDate');

    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $csv_headers[] = $extra_info[$fieldId]['display_text'];
        }
    }
    ob_end_clean();

    $csvContentInSession = Session::read('csv_content', []);

    // Adding headers before the content.
    array_unshift($csvContentInSession, $csv_headers);

    if ($sessionId) {
        $sessionInfo = api_get_session_info($sessionId);
        $sessionDates = SessionManager::parseSessionDates($sessionInfo);

        array_unshift($csvContentInSession, [get_lang('Date'), $sessionDates['access']]);
        array_unshift($csvContentInSession, [get_lang('SessionName'), Security::remove_XSS($sessionInfo['name'])]);
    }

    Export::arrayToCsv($csvContentInSession, 'reporting_student_list');
    exit;
}
if (isset($_GET['csv']) && $_GET['csv'] == 1) {
    $since = 6;
    if (isset($_GET['since'])) {
        if ($_GET['since'] === 'never') {
            $since = 'never';
        } else {
            $since = (int) $_GET['since'];
        }
    }
    $users = Tracking::getInactiveStudentsInCourse(
        api_get_course_int_id(),
        $since,
        $sessionId,
        $parameters['user_active']
    );

    if (count($users) != 0) {
        $csv_content = [];
        $csv_headers = [get_lang('NamesAndLastNames'), get_lang('Classes')];

        $userProfileInfo = [];
        if (isset($_GET['additional_profile_field'])) {
            foreach ($_GET['additional_profile_field'] as $fieldId) {
                $csv_headers[] = $extra_info[$fieldId]['display_text'];
                $userProfileInfo[$fieldId] = TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser(
                    $fieldId,
                    $users
                );
                $extra_info[$fieldId] = $objExtrafieldUser->getFieldInfoByFieldId($fieldId);
            }
        }
        $csv_content[] = $csv_headers;
        $userGroupManager = new UserGroup();

        foreach ($users as $userId) {
            $user = api_get_user_info($userId);
            $classes = implode(
                ', ',
                $userGroupManager->getNameListByUser($userId, UserGroup::NORMAL_CLASS)
            );
            $row = [$user['complete_name'], $classes];

            foreach ($_GET['additional_profile_field'] as $fieldId) {
                $extraFieldInfo = $extra_info[$fieldId];
                if (isset($userProfileInfo[$fieldId]) && isset($userProfileInfo[$fieldId][$userId])) {
                    if (is_array($userProfileInfo[$fieldId][$userId])) {
                        $row[] = implode(
                            ', ',
                            $userProfileInfo[$fieldId][$userId]
                        );
                    } else {
                        $row[] = $userProfileInfo[$fieldId][$userId];
                    }
                } else {
                    $row[] = '';
                }
            }
            $csv_content[] = $row;
        }
        ob_end_clean();
        Export::arrayToCsv($csv_content, 'reporting_inactive_users');
        exit;
    }
}
Display::display_footer();
