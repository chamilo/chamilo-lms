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
$course_info = api_get_course_info(api_get_course_id());
$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

if ($from == 'myspace') {
    $from_myspace = true;
    $this_section = "session_my_space";
} else {
    $this_section = SECTION_COURSES;
}

// Access restrictions.
$is_allowedToTrack = api_is_platform_admin() || api_is_allowed_to_create_course() || api_is_session_admin() || api_is_drh() || api_is_course_tutor();

if (!$is_allowedToTrack) {
    api_not_allowed();
    exit;
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

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$session_id = intval($_REQUEST['id_session']);

if ($export_csv) {
    ob_start();
}

if (empty($session_id)) {
    $session_id = api_get_session_id();
}

// Breadcrumbs.
if (isset($_GET['origin']) && $_GET['origin'] == 'resume_session') {
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'admin/index.php','name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'admin/session_list.php','name' => get_lang('SessionList'));
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.api_get_session_id(), 'name' => get_lang('SessionOverview'));
}

$nameTools = get_lang('Tracking');

// Display the header.
Display::display_header($nameTools, 'Tracking');

/* MAIN CODE */

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('user.png', get_lang('StudentsTracking'), array(), ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq()
);

echo Display::url(
    Display::return_icon('course.png', get_lang('CourseTracking'), array(), ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'tracking/course_log_tools.php?'.api_get_cidreq()
);

echo Display::return_icon('tools_na.png', get_lang('ResourcesTracking'), array(), ICON_SIZE_MEDIUM);
echo '<span style="float:right; padding-top:0px;">';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
    Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).
'</a>';

$addional_param = '';
if (isset($_GET['additional_profile_field'])) {
    $addional_param ='additional_profile_field='.intval($_GET['additional_profile_field']);
}

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page= '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}

echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.$users_tracking_per_page.'">
'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a>';

echo '</span>';
echo '</div>';

// Create a search-box.
$form = new FormValidator(
    'search_simple',
    'GET',
    api_get_path(WEB_CODE_PATH).'tracking/course_log_resources.php?'.api_get_cidreq().'&id_session'.$session_id,
    '',
    array('class' => 'form-search'),
    false
);
$renderer = $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span>');
$form->addElement('text', 'keyword', get_lang('keyword'));
$form->addElement('hidden', 'cidReq', api_get_course_id());
$form->addElement('hidden', 'id_session', $session_id);
$form->addElement('style_submit_button', 'submit', get_lang('SearchUsers'), 'class="search"');
echo '<div class="actions">';
$form->display();
echo '</div>';

$table = new SortableTable(
    'resources',
    array('TrackingCourseLog', 'count_item_resources'),
    array('TrackingCourseLog', 'get_item_resources_data'),
    5,
    20,
    'DESC'
);

$parameters = array(
    'keyword' => Security::remove_XSS($_GET['keyword']),
    'id_session' => $session_id,
    'cidReq' => api_get_course_id()
);

$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Tool'));
$table->set_header(1, get_lang('EventType'));
$table->set_header(2, get_lang('Session'), false);
$table->set_header(3, get_lang('UserName'), true, 'width=65px');
$table->set_header(4, get_lang('IPAddress'), true, 'width=100px');
$table->set_header(5, get_lang('Document'), false);
$table->set_header(6, get_lang('Date'), true, 'width=190px');
$table->display();

Display::display_footer();
