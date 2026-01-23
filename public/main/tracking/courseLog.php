<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CourseBundle\Entity\CQuiz;
use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_TRACKING;
$course = api_get_course_entity();
if (null === $course) {
    api_not_allowed(true);
}
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$is_allowedToTrack = Tracking::isAllowToTrack($sessionId);
$session = api_get_session_entity($sessionId);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

// Keep course_code form as it is loaded (global) by the table's get_user_data.
$courseCode = $course->getCode();
$courseId = $course->getId();
$parameters['cid'] = isset($_GET['cid']) ? (int) $_GET['cid'] : '';
$parameters['id_session'] = $sessionId;
$parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;
$parameters['user_active'] = isset($_REQUEST['user_active']) && is_numeric($_REQUEST['user_active']) ? (int) $_REQUEST['user_active'] : null;

// PERSON_NAME_DATA_EXPORT is buggy.
$sortByFirstName = api_sort_by_first_name();
$from_myspace = false;
$from = $_GET['from'] ?? null;
$origin = api_get_origin();
$lpShowMaxProgress = 'true' === api_get_setting('lp.lp_show_max_progress_instead_of_average');
if ('true' === api_get_setting('lp.lp_show_max_progress_or_average_enable_course_level_redefinition')) {
    $lpShowProgressCourseSetting = api_get_course_setting('lp_show_max_or_average_progress');
    if (in_array($lpShowProgressCourseSetting, ['max', 'average'])) {
        $lpShowMaxProgress = ('max' === $lpShowProgressCourseSetting);
    }
}

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    $from_myspace = true;
    $this_section = 'session_my_space';
}

// If the user is a HR director (drh).
if (api_is_drh()) {
    // Blocking course for drh.
    if (api_drh_can_access_all_session_content()) {
        // If the drh has been configured to be allowed to see all session content, give him access to the session courses.
        $coursesFromSession = SessionManager::getAllCoursesFollowedByUser(api_get_user_id(), null);
        $coursesFromSessionCodeList = [];
        if (!empty($coursesFromSession)) {
            foreach ($coursesFromSession as $courseItem) {
                $coursesFromSessionCodeList[$courseItem['code']] = $courseItem['code'];
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
        // then check if he has also been given access to the corresponding courses.
        $coursesFollowedList = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        $coursesFollowedList = array_keys($coursesFollowedList);
        if (!in_array($courseId, $coursesFollowedList)) {
            api_not_allowed(true);
        }
    }
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
$columnsToHideFromSetting = api_get_setting('course.course_log_hide_columns', true);
$columnsToHide = [0, 8, 9, 10, 11];
if (!empty($columnsToHideFromSetting) && isset($columnsToHideFromSetting['columns'])) {
    $columnsToHide = $columnsToHideFromSetting['columns'];
}
$columnsToHide = json_encode($columnsToHide);
$csv_content = [];
$visibleIcon = Display::return_icon(
    'visible.png',
    get_lang('Hide column'),
    ['align' => 'absmiddle', 'hspace' => '3px']
);

$exportInactiveUsers = api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq().'&'.$additionalParams;

// Scripts for reporting array hide/show columns.
$js = "<script>
    // Hide column and display the button to unhide it.
    function foldup(id) {
        var show = function () { \$(this).css('outline', '1px solid red') };
        var hide = function () { \$(this).css('outline', '1px solid gree') };

        \$('#reporting_table .data_table tr td:nth-child(' + (id + 1) + ')').toggle();
        \$('#reporting_table .data_table tr th:nth-child(' + (id + 1) + ')').toggle();
        \$('div#unhideButtons a:nth-child(' + (id + 1) + ')').toggle();
    }

    // Add the red cross on top of each column.
    function init_hide() {
        \$('#reporting_table .data_table tr th').each(
            function(index) {
                \$(this).prepend(
                    '<div style=\"cursor:pointer\" onclick=\"foldup(' + index + ')\">".Display::getMdiIcon(StateIcon::ACTIVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Hide column'))."</div>'
                );
            }
        );
    }

    // Hide some column at startup.
    // Be sure that these columns always exist (see headers = array()).
    \$(function() {
        init_hide();
        var columnsToHide = ".$columnsToHide.";
        if (columnsToHide) {
            columnsToHide.forEach(function(id) {
                foldup(id);
            });
        }
        \$('#download-csv').on('click', function (e) {
            e.preventDefault();
            location.href = '".$exportInactiveUsers.'&csv=1&since='."'+\$('#reminder_form_since').val();
        });
    })
</script>";
$htmlHeadXtra[] = $js;

$htmlHeadXtra[] = <<<CSSJS
<style>
  /* Panel titles: ensure spacing between icon and text */
  #course-log-main-panel .panel-title i.ch-tool-icon,
  .panel .panel-title i.ch-tool-icon {
    margin-right: 6px;
    vertical-align: middle;
  }

  /* Tables inside panels: avoid fixed width overflow (helps Audit report tables too) */
  #course-log-main-panel .panel-body {
    overflow-x: auto;
  }
  #course-log-main-panel .panel-body table {
    width: 100% !important;
    max-width: 100%;
  }

  .ch-icon-title {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .ch-icon-title i.ch-tool-icon {
      margin-right: 0 !important;
      line-height: 1;
      vertical-align: middle;
    }

  /* Toolbar spacing and compact controls */
  #course_log {
    margin-top: 8px;
    margin-bottom: 8px;
  }

  #course_log .btn,
  #course_log .form-control,
  #course_log select {
    font-size: 13px;
  }

  /* Advanced search container */
  #advanced_search_options {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 14px;
    margin: 8px 0 12px;
  }

  #advanced_search_options .form-group {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 8px;
    align-items: center;
    margin-bottom: 8px;
  }

  #advanced_search_options .form-group > label {
    font-weight: 600;
    margin: 0;
    font-size: 13px;
    color: #374151;
  }

  #advanced_search_options .form-control,
  #advanced_search_options select {
    max-width: 100%;
    font-size: 13px;
    padding: 4px 6px;
    height: auto;
  }

  #advanced_search_options .btn {
    font-size: 13px;
    padding: 4px 10px;
  }

  @media (max-width: 992px) {
    #advanced_search_options .form-group {
      grid-template-columns: 1fr;
    }
  }

  #advanced_search_options .has-long-list > div:last-child {
    max-height: 260px;
    overflow: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px;
    background: #ffffff;
  }

  @media (min-width: 992px) {
    #advanced_search_options .has-long-list > div:last-child {
      column-count: 2;
      column-gap: 16px;
    }

    #advanced_search_options .has-long-list .radio,
    #advanced_search_options .has-long-list .checkbox {
      break-inside: avoid;
    }
  }

  /* Show hidden columns buttons */
  #unhideButtons {
    margin: 8px 0 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
  }

  /* Main reporting table */
  #reporting_table {
    margin-top: 8px;
  }

  #reporting_table .data_table {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
  }

  #reporting_table .data_table th,
  #reporting_table .data_table td {
    padding: 4px 6px;
    font-size: 13px;
    vertical-align: middle;
  }

  #reporting_table .data_table th {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
  }

  #reporting_table .data_table tr:nth-child(even) td {
    background: #fdfdfd;
  }

  /* Free users anchor and button */
  #free-users {
    scroll-margin-top: 80px;
  }

  #free-users .btn {
    font-size: 13px;
    padding: 4px 10px;
  }

  /* Generic grey borders for detailed tables */
  .table.table-bordered > tbody > tr > td,
  .table.table-bordered > thead > tr > th {
    border-color: #e5e7eb;
  }
  .user-teacher,
  .user-coachs {
    list-style: none;
    padding-left: 0;
    margin: 4px 0 0;
  }

  .user-teacher li,
  .user-coachs li {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    padding: 2px 0;
  }
  .course-log-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
  }

  .course-log-nav-link--active .course-log-nav-icon {
    color: #ddd;
  }
  .course-log-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin: 12px 0 4px;
  }

  .course-log-meta__column {
    flex: 1 1 260px;
    min-width: 260px;
  }

  .course-log-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    padding: 10px 12px;
  }

  .course-log-card__header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
  }

  .course-log-card__icon {
    font-size: 18px;
  }

  .course-log-card__title {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
  }

  .course-log-card__body {
    font-size: 13px;
  }

  .course-log-session-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
  }

  .course-log-session-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 3px 0;
    font-size: 13px;
  }
</style>
<script>
  $(function () {
    // Mark long lists to render them in multiple columns.
    $('#advanced_search_options .form-group').each(function () {
      var inputs = $(this).find('input[type=checkbox], input[type=radio]');
      if (inputs.length > 6) {
        $(this).addClass('has-long-list');
      }
    });
  });
</script>
CSSJS;
$htmlHeadXtra[] = <<<CSSJS
<style>
/* Extra fields form: grid layout and spacing */
#advanced_search_options #extra_fields {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 12px 18px;
  align-items: flex-start;
  margin-top: 6px;
}

/* Each span behaves like a vertical field block */
#advanced_search_options #extra_fields > span {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Labels inside extra_fields */
#advanced_search_options #extra_fields label {
  font-weight: 600;
  font-size: 13px;
  color: #374151;
  margin-bottom: 2px;
}

#advanced_search_options #extra_hobbies {
  min-height: 80px;
}
#advanced_search_options #extra_fields .field-radiobutton {
  width: 50%;
  float: left;
}

/* Inputs and selects full width */
#advanced_search_options #extra_fields input[type="text"],
#advanced_search_options #extra_fields select,
#advanced_search_options #extra_fields .flatpickr-wrapper,
#advanced_search_options #extra_fields .p-inputtext,
#advanced_search_options #extra_fields .p-select {
  width: 100%;
}

/* Radio groups in a clean vertical list */
#advanced_search_options #extra_fields .field-radiobutton {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 4px;
}

#advanced_search_options #extra_fields .field-radiobutton label {
  margin: 0;
}

.tracking-box-title {
  font-size: 18px;
  text-align: center;
}

/* Main panel: give some breathing room inside */
#course-log-main-panel .panel-body {
  padding: 18px 22px;
}

#course-log-main-panel .card, .card {
  padding: 8px;
}

.card .field-checkbox, .card .field-radiobutton {
  justify-content: normal;
}
</style>
CSSJS;

// Database table definitions.
// @todo remove these calls.
$TABLETRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);

$userEditionExtraFieldToCheck = 'true' === api_get_setting('workflows.user_edition_extra_field_to_check');

// Breadcrumbs.
if ('resume_session' === $origin) {
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
Event::event_access_tool(TOOL_TRACKING);
$tpl = new Template('');

// Getting all the students of the course.
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

    $extraField = new ExtraField('user');
    foreach ($_GET['additional_profile_field'] as $fieldId) {
        // Fetching only the users that are loaded, not all users in the portal.
        $userProfileInfo[$fieldId] = TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser(
            $fieldId,
            $user_array
        );
        $extra_info[$fieldId] = $extraField->getFieldInfoByFieldId($fieldId);
    }
}

Session::write('additional_user_profile_info', $userProfileInfo);
Session::write('extra_field_info', $extra_info);

$defaultExtraFields = [];
$defaultExtraFieldsFromSettings = api_get_setting('course.course_log_default_extra_fields', true);
if (!empty($defaultExtraFieldsFromSettings) && isset($defaultExtraFieldsFromSettings['extra_fields'])) {
    $defaultExtraFields = $defaultExtraFieldsFromSettings['extra_fields'];
    $defaultExtraInfo = [];
    $defaultUserProfileInfo = [];

    foreach ($defaultExtraFields as $fieldName) {
        $extraFieldInfo = UserManager::get_extra_field_information_by_name($fieldName);

        if (!empty($extraFieldInfo)) {
            // Fetching only the users that are loaded, not all users in the portal.
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

$actionsLeft = TrackingCourseLog::actionsLeft('users', $sessionId, false);

$actionsRight = '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
    Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Print')).'</a>';

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page = '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}

$showNonRegistered = isset($_GET['show_non_registered']) ? (int) $_GET['show_non_registered'] : 0;
$freeUsers = [];
if ($showNonRegistered) {
    $freeUsers = Statistics::getNonRegisteredActiveUsersInCourse($courseId, (int) $sessionId);
}

$actionsRight .= '<a
    href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$additionalParams.$users_tracking_per_page.'">
     '.Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('CSV export')).'</a>';

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
$renderer->setCustomElementTemplate('<span>{element}</span>');
$form_search->addHidden('from', Security::remove_XSS($from));
$form_search->addHidden('sessionId', $sessionId);
$form_search->addHidden('sid', $sessionId);
$form_search->addHidden('cid', $courseId);
$form_search->addElement('text', 'user_keyword');
$form_search->addButtonSearch(get_lang('Search users'));
echo Display::toolbarAction(
    'course_log',
    [$actionsLeft, $form_search->returnForm(), $actionsRight]
);

$courseTitle = (string) $course->getTitle();
$courseDisplay = get_lang('Course').' '.$courseTitle;

if ($sessionId) {
    $titleSession = Display::getMdiIcon(
            ObjectIcon::SESSION,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Session')
        ).'&nbsp;'.api_get_session_name($sessionId);

    $titleCourse = Display::getMdiIcon(
            ObjectIcon::COURSE,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Course')
        ).'&nbsp;'.$courseDisplay;
} else {
    // When there is no session, show only course info.
    $titleSession = Display::getMdiIcon(
            ObjectIcon::COURSE,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Course')
        ).'&nbsp;'.$courseDisplay;
    $titleCourse = '';
}

$panelIcon = Display::getMdiIcon(
    ToolIcon::TRACKING,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL,
    get_lang('Reporting')
);

// Panel title: tracking icon + course (if any) + session
if ($sessionId) {
    $panelTitle = $panelIcon.'&nbsp;'.$titleCourse.' &raquo; '.$titleSession;
} else {
    $panelTitle = $panelIcon.'&nbsp;'.$titleSession;
}

$teacherList = CourseManager::getTeacherListFromCourseCodeToString(
    $courseCode,
    ',',
    true,  // Add link to profile
    true   // Render as <ul> list
);

$coaches = null;
if (!empty($sessionId)) {
    $coaches = CourseManager::get_coachs_from_course_to_string(
        $sessionId,
        $courseId,
        ',',
        true,  // Add link to profile
        true   // Render as <ul> list
    );
}

/** @var string[] $sessionLinks */
$sessionLinks = [];
$showReporting = ('false' === api_get_setting('session.hide_reporting_session_list'));

if ($showReporting) {
    $sessionList = SessionManager::get_session_by_course($courseId);

    if (!empty($sessionList)) {
        $icon = Display::getMdiIcon(
            ObjectIcon::SESSION,
            'ch-tool-icon course-log-session-icon',
            null,
            ICON_SIZE_TINY
        );

        $urlWebCode = api_get_path(WEB_CODE_PATH);
        $isAdmin = api_is_platform_admin();

        foreach ($sessionList as $sessionItem) {
            if (!$isAdmin) {
                // Respect session visibility for non-admin users.
                $visibility = api_get_session_visibility($sessionItem['id'], $courseId);
                if (SESSION_INVISIBLE === $visibility) {
                    continue;
                }

                // Only show sessions where user is coach.
                $isCoach = api_is_coach($sessionItem['id'], $courseId);
                if (!$isCoach) {
                    continue;
                }
            }

            $url = $urlWebCode.'tracking/courseLog.php?cid='.$courseId.'&sid='.$sessionItem['id'].'&gid=0';

            $sessionLinks[] =
                '<li class="course-log-session-item">'.
                $icon.
                Display::url(Security::remove_XSS($sessionItem['title']), $url).
                '</li>';
        }
    }
}

$html = '';

if (!empty($teacherList) || !empty($coaches) || !empty($sessionLinks)) {
    $html .= '<div class="course-log-meta">';

    // Column: trainers / coaches
    if (!empty($teacherList) || !empty($coaches)) {
        $html .= '<div class="course-log-meta__column">';
        $html .= '<div class="course-log-card">';

        $html .= '<div class="course-log-card__header">';
        $html .= Display::getMdiIcon(
            'account-tie-outline',
            'ch-tool-icon course-log-card__icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Trainers')
        );
        $html .= '<span class="course-log-card__title">'.get_lang('Trainers').'</span>';
        $html .= '</div>';

        $html .= '<div class="course-log-card__body">';

        // Teacher list (returns an UL when orderList = true)
        if (!empty($teacherList)) {
            $html .= $teacherList;
        }

        // Coaches list
        if (!empty($coaches)) {
            $html .= '<div class="course-log-card__subsection-title">'.get_lang('Coaches').'</div>';
            $html .= $coaches;
        }

        $html .= '</div>'; // .course-log-card__body
        $html .= '</div>'; // .course-log-card
        $html .= '</div>'; // .course-log-meta__column
    }

    // Column: session list
    if (!empty($sessionLinks)) {
        $html .= '<div class="course-log-meta__column">';
        $html .= '<div class="course-log-card">';

        $html .= '<div class="course-log-card__header">';
        $html .= Display::getMdiIcon(
            ObjectIcon::SESSION,
            'ch-tool-icon course-log-card__icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Session list')
        );
        $html .= '<span class="course-log-card__title">'.get_lang('Session list').'</span>';
        $html .= '</div>';

        $html .= '<div class="course-log-card__body">';
        $html .= '<ul class="course-log-session-list">'.implode('', $sessionLinks).'</ul>';
        $html .= '</div>'; // .course-log-card__body

        $html .= '</div>'; // .course-log-card
        $html .= '</div>'; // .course-log-meta__column
    }

    $html .= '</div>'; // .course-log-meta
}

$trackingColumn = $_GET['users_tracking_column'] ?? null;
$trackingDirection = $_GET['users_tracking_direction'] ?? null;
$hideReports = api_get_configuration_value('hide_course_report_graph');
$conditions = [];

$groupList = GroupManager::get_group_list(null, $course, 1, $sessionId);
$class = new UserGroupModel();
$classes = $class->get_all();

$bestScoreLabel = get_lang('Score').' - '.get_lang('Best attempt');

// Show the charts part only if there are students subscribed to this course/session.
if ($nbStudents > 0 || isset($parameters['user_active'])) {
    // Classes.
    $formClass = new FormValidator(
        'classes',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $formClass->addHidden('cid', $courseId);
    $formClass->addHidden('sid', $sessionId);
    $groupIdList = ['--'];
    $select = $formClass->addSelect('class_id', get_lang('Class').'/'.get_lang('Group'), $groupIdList);
    $groupIdList = [];
    foreach ($classes as $class) {
        $groupIdList[] = ['text' => $class['title'], 'value' => 'class_'.$class['id']];
    }
    $select->addOptGroup($groupIdList, get_lang('Class'));
    $groupIdList = [];
    foreach ($groupList as $group) {
        $groupIdList[] = ['text' => $group['title'], 'value' => 'group_'.$group['iid']];
    }
    $select->addOptGroup($groupIdList, get_lang('Group'));
    $formClass->addButtonSearch(get_lang('Search'));

    // Extra fields.
    $formExtraField = new FormValidator(
        'extra_fields',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $formExtraField->addHidden('cid', $courseId);
    $formExtraField->addHidden('sid', $sessionId);
    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $fieldId = Security::remove_XSS($fieldId);
            $formExtraField->addHidden('additional_profile_field[]', $fieldId);
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
        $courseId,
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

    if ($formExtraField->validate()) {
        $extraResult = $extraField->processExtraFieldSearch($_REQUEST, $formExtraField, 'user');
        if (!empty($extraResult)) {
            $conditions = $extraResult['condition'];
            $fields = $extraResult['fields'];
        }
    }

    if (false === $hideReports) {
        $conditions['course_id'] = $courseId;
        $conditions['include_invited_users'] = false;
        $usersTracking = TrackingCourseLog::getUserData(
            0,
            $nbStudents,
            $trackingColumn,
            $trackingDirection,
            $conditions,
            true,
            false,
            null,
            (int) $sessionId,
            $export_csv,
            $user_ids
        );
        $userRepo = Container::getUserRepository();
        foreach ($usersTracking as $userTracking) {
            $user = $userRepo->findOneBy(['username' => $userTracking[3]]);
            if (empty($user)) {
                continue;
            }
            $userId = $user->getId();
            if ('100%' === $userTracking[5]) {
                $numberStudentsCompletedLP++;
            }
            $averageStudentTestScore = substr($userTracking[7], 0, -1);
            $averageStudentsTestScore .= $averageStudentTestScore;

            $reducedAverage = ('100' === $averageStudentTestScore) ? 9 : floor((float) $averageStudentTestScore / 10);
            if (isset($scoresDistribution[$reducedAverage])) {
                $scoresDistribution[$reducedAverage]++;
            }

            $scoreStudent = (float) substr($userTracking[5], 0, -1) + (float) substr($userTracking[7], 0, -1);
            [$hours, $minutes, $seconds] = preg_split('/:/', $userTracking[4]);
            $minutes = round((3600 * (int) $hours + 60 * (int) $minutes + (int) $seconds) / 60);

            $certificate = false;
            if (isset($category[0]) && $category[0]->is_certificate_available($userId)) {
                $certificate = true;
                $certificateCount++;
            }

            $listStudent = [
                'id' => $userId,
                'user' => $user,
                'fullname' => UserManager::formatUserFullName($user),
                'score' => floor($scoreStudent / 2),
                'total_time' => $minutes,
                'certicate' => $certificate,
            ];
            $listStudentIds[] = $userId;
            $userScoreList[] = $listStudent;
        }

        uasort($userScoreList, 'sort_by_order');
        $averageStudentsTestScore = round($averageStudentsTestScore / $nbStudents);

        $colors = ChamiloHelper::getColorPalette(true, true, 10);
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

$html .= '<div style="margin-top: 16px;"></div>';
$html .= Display::page_subheader2(
    '<span class="ch-icon-title">'.
    Display::getMdiIcon(
        'account-multiple-outline',
        'ch-tool-icon',
        null,
        ICON_SIZE_TINY,
        get_lang('Learner list')
    ).
    '<span>'.get_lang('Learner list').'</span>'.
    '</span>'
);

$bestScoreLabel = get_lang('Score').' - '.get_lang('Only best attempts');
if ($nbStudents > 0) {
    $mainForm = new FormValidator(
        'filter',
        'get',
        api_get_self().'?'.api_get_cidreq().'&'.$additionalParams
    );
    $mainForm->addButtonAdvancedSettings(
        'advanced_search',
        [get_lang('Advanced search')]
    );
    $mainForm->addHtml('<div id="advanced_search_options" style="display:none;">');
    $mainForm->addHtml($formClass->returnForm());
    $mainForm->addHtml($formExtraField->returnForm());
    $mainForm->addHtml('</div>');

    $html .= $mainForm->returnForm();

    $getLangXDays = get_lang('%s days');
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
        Display::getMdiIcon(StateIcon::WARNING, 'ch-tool-icon', null, ICON_SIZE_SMALL).get_lang('Remind learners inactive since'),
        $options,
        ['disable_js' => true, 'class' => 'col-sm-3']
    );
    $el->setSelected(7);
    $form->addElement('hidden', 'action', 'add');
    $form->addElement('hidden', 'remindallinactives', 'true');
    $form->addElement('hidden', 'cid', api_get_course_int_id());
    $form->addElement('hidden', 'sid', api_get_session_id());
    $form->addButtonSend(get_lang('Notify'));

    $extraFieldSelect = TrackingCourseLog::displayAdditionalProfileFields();
    if (!empty($extraFieldSelect)) {
        $html .= $extraFieldSelect;
    }

    $html .= $form->returnForm();

    if ($export_csv) {
        $csv_content = [];
        // Override the SortableTable "per page" limit if CSV.
        $_GET['users_tracking_per_page'] = 1000000;
    }

    if (false === $hideReports) {
        $table = new SortableTableFromArray(
            $usersTracking,
            1,
            20,
            'users_tracking'
        );
        $table->total_number_of_items = $nbStudents;
    } else {
        $conditions['include_invited_users'] = true;
        $conditions['course_id'] = $courseId;
        $table = new SortableTable(
            'users_tracking',
            ['TrackingCourseLog', 'get_number_of_users'],
            ['TrackingCourseLog', 'get_user_data'],
            1,
            20
        );
        $table->setDataFunctionParams($conditions);
    }

    $parameters['cid'] = isset($_GET['cid']) ? Security::remove_XSS($_GET['cid']) : '';
    $parameters['sid'] = $sessionId;
    $parameters['from'] = isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

    $headerCounter = 0;
    $headers = [];
    // Tab of header texts.
    $table->set_header($headerCounter++, get_lang('Code'), true);
    $headers['official_code'] = get_lang('Code');
    if ($sortByFirstName) {
        $table->set_header($headerCounter++, get_lang('First name'), true);
        $table->set_header($headerCounter++, get_lang('Last name'), true);
        $headers['firstname'] = get_lang('First name');
        $headers['lastname'] = get_lang('Last name');
    } else {
        $table->set_header($headerCounter++, get_lang('Last name'), true);
        $table->set_header($headerCounter++, get_lang('First name'), true);
        $headers['lastname'] = get_lang('Last name');
        $headers['firstname'] = get_lang('First name');
    }
    $table->set_header($headerCounter++, get_lang('Login'), false);
    $headers['login'] = get_lang('Login');

    $table->set_header(
        $headerCounter++,
        get_lang('Time').'&nbsp;'.
        Display::getMdiIcon('clock-outline', 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Time spent in the course')),
        false
    );
    $headers['training_time'] = get_lang('Time');
    $table->set_header(
        $headerCounter++,
        get_lang('Progress').'&nbsp;'.
        Display::getMdiIcon(ObjectIcon::COURSE_PROGRESS, 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Average progress in courses')),
        false
    );
    $headers['course_progress'] = get_lang('Course progress');

    $table->set_header(
        $headerCounter++,
        get_lang('Exercise progress').'&nbsp;'.
        Display::getMdiIcon(ObjectIcon::COURSE_PROGRESS, 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Progress of exercises taken by the student')),
        false
    );
    $headers['exercise_progress'] = get_lang('Exercise progress');
    $table->set_header(
        $headerCounter++,
        get_lang('Exercise average').'&nbsp;'.
        Display::getMdiIcon('format-annotation-plus', 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Average of best grades of each exercise attempt')),
        false
    );
    $headers['exercise_average'] = get_lang('Exercise average');
    $table->set_header(
        $headerCounter++,
        get_lang('Score').'&nbsp;'.
        Display::getMdiIcon('format-annotation-plus', 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Average of tests in Learning Paths')),
        false
    );
    $headers['score'] = get_lang('Score');
    $table->set_header(
        $headerCounter++,
        $bestScoreLabel.'&nbsp;'.
        Display::getMdiIcon('format-annotation-plus', 'ch-tool-icon', null, ICON_SIZE_TINY, get_lang('Average of tests in Learning Paths')),
        false
    );
    $headers['score_best'] = $bestScoreLabel;

    $addExerciseOption = api_get_setting('exercise.add_exercise_best_attempt_in_report', true);
    $exerciseResultHeaders = [];
    if (!empty($addExerciseOption) && isset($addExerciseOption['courses']) && isset($addExerciseOption['courses'][$courseCode])) {
        foreach ($addExerciseOption['courses'][$courseCode] as $exerciseId) {
            $exercise = new Exercise();
            $exercise->read($exerciseId);
            if ($exercise->iId) {
                $title = get_lang('Test').': '.$exercise->get_formated_title();
                $table->set_header($headerCounter++, $title, false);
                $exerciseResultHeaders[] = $title;
                $headers['exercise_'.$exercise->iId] = $title;
            }
        }
    }

    $table->set_header($headerCounter++, get_lang('Assignments'), false);
    $headers['student_publication'] = get_lang('Assignments');
    $table->set_header($headerCounter++, get_lang('Messages'), false);
    $headers['messages'] = get_lang('Messages');
    $table->set_header($headerCounter++, get_lang('Classes'));
    $headers['classes'] = get_lang('Classes');

    if (empty($sessionId)) {
        $table->set_header($headerCounter++, get_lang('Survey'), false);
        $headers['survey'] = get_lang('Survey');
    } else {
        $table->set_header($headerCounter++, get_lang('Registered date'), false);
        $headers['registered_at'] = get_lang('Registered date');
    }
    $table->set_header($headerCounter++, get_lang('First access to course'), false);
    $headers['first_login'] = get_lang('First access to course');
    $table->set_header($headerCounter++, get_lang('Latest access in course'), false);
    $headers['latest_login'] = get_lang('Latest access in course');
    $table->set_header($headerCounter++, get_lang("Last lp's finalization date"), false);
    $headers['lp_finalization_date'] = get_lang("Last lp's finalization date");
    $table->set_header($headerCounter++, get_lang('Last quiz finalization date'), false);
    $headers['quiz_finalization_date'] = get_lang('Last quiz finalization date');

    $counter = $headerCounter;
    if ('true' === api_get_setting('show_email_addresses')) {
        $table->set_header($counter, get_lang('E-mail'), false);
        $headers['email'] = get_lang('E-mail');
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
    $parameters['cid'] = api_get_course_int_id();
    $parameters['sid'] = $sessionId;
    $table->set_additional_parameters($parameters);
    // Display buttons to unhide hidden columns.
    $html .= '<div id="unhideButtons" class="btn-toolbar">';
    $index = 0;
    $getLangDisplayColumn = get_lang('Show column');
    foreach ($headers as $header) {
        $html .= Display::toolbarButton(
            $header,
            '#',
            'arrow-right',
            'plain-outline',
            [
                'title' => htmlentities("$getLangDisplayColumn \"$header\"", ENT_QUOTES),
                'style' => 'display: none;',
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
    if (empty($freeUsers)) {
        $html .= Display::return_message(get_lang('No users in course'), 'warning', true);
    }
}

// Small top margin between charts (tpl) and info panel
// Small top margin between charts (tpl) and info panel
echo '<div id="course-log-main-panel" style="margin-top: 16px;">';
echo Display::panel($html, $panelTitle);
echo '</div>';


$freeAnchor = 'free-users';
$toggleUrl  = api_get_self().'?'.api_get_cidreq().'&show_non_registered='.($showNonRegistered ? 0 : 1).'#'.$freeAnchor;
$toggleLbl  = $showNonRegistered
    ? get_lang('Hide free users (not enrolled)')
    : get_lang('Show free users (not enrolled)');

echo '<div id="'.$freeAnchor.'" class="mb-2" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
    .'<a class="btn btn--info" href="'.$toggleUrl.'">'.Security::remove_XSS($toggleLbl).'</a>'
    .'</div>';

$groupTable = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table']);
$column = 0;
$groupTable->setHeaderContents(0, $column++, get_lang('Name'));
$groupTable->setHeaderContents(0, $column++, get_lang('Time'));
$groupTable->setHeaderContents(0, $column++, get_lang('Average time in the course'));
$groupTable->setHeaderContents(0, $column++, get_lang('Course progress'));
$groupTable->setHeaderContents(0, $column++, get_lang('Exercise average'));

$exerciseList = [];
$session = api_get_session_entity($sessionId);
$qb = Container::getQuizRepository()->findAllByCourse($course, $session, null, 2, false);
/** @var CQuiz[] $exercises */
$exercises = $qb->getQuery()->getResult();

$percentVal = static function ($v): float {
    if ($v === '' || $v === null) {
        return 0.0;
    }
    if (is_numeric($v)) {
        return (float) $v;
    }

    return (float) str_replace('%', '', trim((string) $v));
};

if (!empty($groupList)) {
    $row = 1;
    $totalSeconds = 0;
    $totalUsers   = 0;
    $sumProgress  = 0.0;
    $sumBest      = 0.0;

    foreach ($groupList as $groupInfo) {
        $col = 0;
        $groupTable->setCellContents($row, $col++, $groupInfo['title']);

        $timeStr = $avgTimeStr = $progStr = $bestStr = '';

        $usersInGroup = GroupManager::getStudents($groupInfo['iid']);
        if (!empty($usersInGroup)) {
            $uids = array_column($usersInGroup, 'user_id');
            $count = count($uids);

            $secs = Tracking::get_time_spent_on_the_course($uids, $courseId, $sessionId);
            if ($secs) {
                $timeStr    = api_time_to_hms($secs);
                $avgTimeStr = api_time_to_hms($secs / $count);
            }

            $groupProgressNum = $percentVal(
                Tracking::get_avg_student_progress($uids, $course, [], $session)
            );
            $progStr = round($groupProgressNum, 2).' %';

            $groupBestNum = 0.0;
            if (!empty($exercises) && $count) {
                $bestSum = 0.0;
                foreach ($exercises as $exerciseData) {
                    foreach ($uids as $u) {
                        $results = Event::get_best_exercise_results_by_user(
                            $exerciseData->getIid(),
                            $courseId,
                            0, // No-session in this query.
                            $u
                        );
                        $best = 0.0;
                        if (!empty($results)) {
                            foreach ($results as $r) {
                                if (!empty($r['max_score'])) {
                                    $sc = $r['score'] / $r['max_score'];
                                    if ($sc > $best) {
                                        $best = $sc;
                                    }
                                }
                            }
                        }
                        $bestSum += $best;
                    }
                }
                $groupBestNum = round(($bestSum / max(count($exercises), 1)) * 100 / $count, 2);
            }
            $bestStr = $groupBestNum ? ($groupBestNum.' %') : '';

            $totalSeconds += $secs;
            $totalUsers   += $count;
            $sumProgress  += $groupProgressNum * $count;
            $sumBest      += $groupBestNum     * $count;
        }

        $groupTable->setCellContents($row, $col++, $timeStr);
        $groupTable->setCellContents($row, $col++, $avgTimeStr);
        $groupTable->setCellContents($row, $col++, $progStr);
        $groupTable->setCellContents($row, $col++, $bestStr);
        $row++;
    }

    if ($showNonRegistered && !empty($freeUsers)) {
        foreach ($freeUsers as $fu) {
            $col = 0;
            $uid  = (int) ($fu['id'] ?? 0);
            $name = Security::remove_XSS(trim(($fu['firstname'] ?? '').' '.($fu['lastname'] ?? ''))).' (free)';

            $secs = Tracking::get_time_spent_on_the_course([$uid], $courseId, $sessionId);
            $timeStr    = $secs ? api_time_to_hms($secs) : '';
            $avgTimeStr = $timeStr;

            $lpNum = $percentVal(Tracking::get_avg_student_progress($uid, $course, [], $session));
            $progStr = $lpNum !== null ? round($lpNum, 2).' %' : '';

            $bestNum = 0.0;
            if (!empty($exercises)) {
                $sumBestFree = 0.0;
                $countE = 0;
                foreach ($exercises as $exerciseData) {
                    $results = Event::get_best_exercise_results_by_user(
                        $exerciseData->getIid(),
                        $courseId,
                        $sessionId,
                        $uid
                    );
                    $best = 0.0;
                    if (!empty($results)) {
                        foreach ($results as $r) {
                            if (!empty($r['max_score'])) {
                                $sc = $r['score'] / $r['max_score'];
                                if ($sc > $best) {
                                    $best = $sc;
                                }
                            }
                        }
                    }
                    $sumBestFree += $best;
                    $countE++;
                }
                if ($countE > 0) {
                    $bestNum = round(($sumBestFree / $countE) * 100, 2);
                }
            }
            $bestStr = $bestNum ? ($bestNum.' %') : '';

            $totalSeconds += $secs;
            $totalUsers   += 1;
            $sumProgress  += $lpNum;
            $sumBest      += $bestNum;

            $groupTable->setCellContents($row, $col++, $name);
            $groupTable->setCellContents($row, $col++, $timeStr);
            $groupTable->setCellContents($row, $col++, $avgTimeStr);
            $groupTable->setCellContents($row, $col++, $progStr);
            $groupTable->setCellContents($row, $col++, $bestStr);
            $row++;
        }
    }

    $avgSecondsAll   = $totalUsers ? ($totalSeconds / $totalUsers) : 0;
    $totalAvgTimeStr = api_time_to_hms($avgSecondsAll);
    $totalProgStr    = $totalUsers ? (round($sumProgress / $totalUsers, 2).' %') : '';
    $totalBestStr    = $totalUsers ? (round($sumBest     / $totalUsers, 2).' %') : '';

    $col = 0;
    $groupTable->setCellContents($row, $col++, get_lang('Total'));
    $groupTable->setCellContents($row, $col++, api_time_to_hms($totalSeconds));
    $groupTable->setCellContents($row, $col++, $totalAvgTimeStr);
    $groupTable->setCellContents($row, $col++, $totalProgStr);
    $groupTable->setCellContents($row, $col++, $totalBestStr);

} else {
    $studentIdList = Session::read('user_id_list');
    $studentIdList = !empty($studentIdList) ? $studentIdList : array_column($studentList, 'user_id');

    if ($showNonRegistered && !empty($freeUsers)) {
        foreach ($freeUsers as $fu) {
            $studentIdList[] = (int) ($fu['id'] ?? 0);
        }
    }

    $nbAll = count($studentIdList);
    $totalSeconds   = Tracking::get_time_spent_on_the_course($studentIdList, $courseId, $sessionId);
    $avgSecondsAll  = $nbAll ? $totalSeconds / $nbAll : 0;

    $sumProgress = 0.0;
    foreach ($studentIdList as $uid) {
        $sumProgress += $percentVal(Tracking::get_avg_student_progress($uid, $course, [], $session));
    }
    $avgProgress = $nbAll ? round($sumProgress / $nbAll, 2) : 0.0;

    $bestSum = 0.0;
    if (!empty($exercises)) {
        foreach ($studentIdList as $uid) {
            $sumE = 0.0;
            $countE = 0;
            foreach ($exercises as $exerciseData) {
                $results = Event::get_best_exercise_results_by_user(
                    $exerciseData->getIid(),
                    $courseId,
                    $sessionId,
                    $uid
                );
                $best = 0.0;
                if (!empty($results)) {
                    foreach ($results as $r) {
                        if (!empty($r['max_score'])) {
                            $sc = $r['score'] / $r['max_score'];
                            if ($sc > $best) {
                                $best = $sc;
                            }
                        }
                    }
                }
                $sumE += $best;
                $countE++;
            }
            if ($countE > 0) {
                $bestSum += ($sumE / $countE) * 100;
            }
        }
    }
    $avgBest = $nbAll ? round($bestSum / $nbAll, 2) : 0.0;

    $row = 1;
    if ($showNonRegistered && !empty($freeUsers)) {
        foreach ($freeUsers as $fu) {
            $col = 0;
            $uid  = (int) ($fu['id'] ?? 0);
            $name = Security::remove_XSS(trim(($fu['firstname'] ?? '').' '.($fu['lastname'] ?? ''))).' (free)';

            $secs = Tracking::get_time_spent_on_the_course([$uid], $courseId, $sessionId);
            $timeU    = $secs ? api_time_to_hms($secs) : '';
            $avgTimeU = $timeU;

            $lpU = $percentVal(Tracking::get_avg_student_progress($uid, $course, [], $session));
            $lpU = round($lpU, 2).' %';

            $bestAvgU = '';
            if (!empty($exercises)) {
                $sumBestU = 0.0;
                $countE = 0;
                foreach ($exercises as $exerciseData) {
                    $results = Event::get_best_exercise_results_by_user(
                        $exerciseData->getIid(),
                        $courseId,
                        $sessionId,
                        $uid
                    );
                    $best = 0.0;
                    if (!empty($results)) {
                        foreach ($results as $r) {
                            if (!empty($r['max_score'])) {
                                $sc = $r['score'] / $r['max_score'];
                                if ($sc > $best) {
                                    $best = $sc;
                                }
                            }
                        }
                    }
                    $sumBestU += $best;
                    $countE++;
                }
                if ($countE > 0) {
                    $bestAvgU = round(($sumBestU / $countE) * 100, 2).' %';
                }
            }

            $groupTable->setCellContents($row, $col++, $name);
            $groupTable->setCellContents($row, $col++, $timeU);
            $groupTable->setCellContents($row, $col++, $avgTimeU);
            $groupTable->setCellContents($row, $col++, $lpU);
            $groupTable->setCellContents($row, $col++, $bestAvgU);
            $row++;
        }
    }

    $col = 0;
    $groupTable->setCellContents($row, $col++, get_lang('Total'));
    $groupTable->setCellContents($row, $col++, api_time_to_hms($totalSeconds));
    $groupTable->setCellContents($row, $col++, api_time_to_hms($avgSecondsAll));
    $groupTable->setCellContents($row, $col++, $avgProgress.' %');
    $groupTable->setCellContents($row, $col++, $avgBest.' %');
}

$groupPanelTitle = Display::getMdiIcon(
        'chart-bar',
        'ch-tool-icon',
        null,
        ICON_SIZE_TINY,
        get_lang('Group reporting')
    ).'&nbsp;'.get_lang('Group reporting');

echo Display::panel($groupTable->toHtml(), $groupPanelTitle);

// Send the csv file if asked.
if ($export_csv) {
    $csv_headers = [];
    $csv_headers[] = get_lang('Code');
    if ($sortByFirstName) {
        $csv_headers[] = get_lang('First name');
        $csv_headers[] = get_lang('Last name');
    } else {
        $csv_headers[] = get_lang('Last name');
        $csv_headers[] = get_lang('First name');
    }
    $csv_headers[] = get_lang('Login');
    $csv_headers[] = get_lang('Time');
    $csv_headers[] = get_lang('Progress');
    $csv_headers[] = get_lang('Exercise progress');
    $csv_headers[] = get_lang('Exercise average');
    $csv_headers[] = get_lang('Score');
    $csv_headers[] = $bestScoreLabel;
    if (!empty($exerciseResultHeaders)) {
        foreach ($exerciseResultHeaders as $exerciseLabel) {
            $csv_headers[] = $exerciseLabel;
        }
    }
    $csv_headers[] = get_lang('Assignments');
    $csv_headers[] = get_lang('Messages');

    if (empty($sessionId)) {
        $csv_headers[] = get_lang('Survey');
    } else {
        $csv_headers[] = get_lang('Registration date');
    }

    $csv_headers[] = get_lang('First access to course');
    $csv_headers[] = get_lang('Latest access in course');
    $csv_headers[] = get_lang("Last lp's finalization date");
    $csv_headers[] = get_lang('Last quiz finalization date');

    if (isset($_GET['additional_profile_field'])) {
        foreach ($_GET['additional_profile_field'] as $fieldId) {
            $csv_headers[] = $extra_info[$fieldId]['display_text'];
        }
    }
    ob_end_clean();

    $csvContentInSession = Session::read('csv_content', []);
    array_unshift($csvContentInSession, $csv_headers);

    if ($sessionId) {
        $session = api_get_session_entity($sessionId);
        $sessionDates = SessionManager::parseSessionDates($session);

        array_unshift($csvContentInSession, [get_lang('Date'), $sessionDates['access']]);
        array_unshift($csvContentInSession, [get_lang('Session name'), $session->getTitle()]);
    }

    Export::arrayToCsv($csvContentInSession, 'reporting_student_list');
    exit;
}

Display::display_footer();

function sort_by_order($a, $b)
{
    return $a['score'] <= $b['score'];
}
