<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

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
    api_not_allowed(true);
    exit;
}

$course_id = api_get_course_int_id();
$course_code = api_get_course_id();
$sessionId = api_get_session_id();

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_group_reporting&course_id='.$course_id.'&session_id='.$sessionId;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('Name'),
    get_lang('Time'),
    get_lang('Progress'),
    get_lang('Score'),
    get_lang('Works'),
    get_lang('Messages'),
    get_lang('Actions'),
);

// Column config
$column_model = array(
    array(
        'name' => 'name',
        'index' => 'name',
        'width' => '200',
        'align' => 'left',
    ),
    array(
        'name' => 'time',
        'index' => 'time',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'progress',
        'index' => 'progress',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'score',
        'index' => 'score',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'works',
        'index' => 'works',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'messages',
        'index' => 'messages',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'actions',
        'index' => 'actions',
        'width' => '50',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ),
);

// Autowidth
$extra_params['autowidth'] = 'true';
// height auto
$extra_params['height'] = 'auto';

$action_links = '
function action_formatter(cellvalue, options, rowObject) {
    return \'<a href="course_log_tools.php?id_session=0&cidReq='.$course_code.'&gidReq=\'+options.rowId+\'">'.Display::return_icon('2rightarrow.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
    '\';
}';


// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = '
<script>
$(function() {
    '.Display::grid_js('group_users', $url, $columns, $column_model, $extra_params, array(), $action_links, true).'
});
</script>';

Display::display_header();

echo '<div class="actions">';
echo Display::url(Display::return_icon('user.png', get_lang('StudentsTracking'), array(), ICON_SIZE_MEDIUM), 'courseLog.php?'.api_get_cidreq(true, false));
echo Display::url(Display::return_icon('group_na.png', get_lang('GroupReporting'), array(), ICON_SIZE_MEDIUM), '#');
echo Display::url(Display::return_icon('course.png', get_lang('CourseTracking'), array(), ICON_SIZE_MEDIUM), 'course_log_tools.php?'.api_get_cidreq(true, false));
echo Display::url(Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), ICON_SIZE_MEDIUM), 'course_log_resources.php?'.api_get_cidreq(true, false));
echo '</div>';

echo Display::grid_html('group_users');

Display::display_footer();
