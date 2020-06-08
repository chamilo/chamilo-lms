<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

$course_id = api_get_course_int_id();
$course_code = api_get_course_id();
$sessionId = api_get_session_id();

$this_section = SECTION_COURSES;
if ('myspace' == $from) {
    $from_myspace = true;
    $this_section = "session_my_space";
}

// Access restrictions.
$is_allowedToTrack = Tracking::isAllowToTrack($sessionId);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
    exit;
}

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_group_reporting&course_id='.$course_id.'&session_id='.$sessionId;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('Time'),
    get_lang('Progress'),
    get_lang('Score'),
    get_lang('Works'),
    get_lang('Messages'),
    get_lang('Actions'),
];

// Column config
$column_model = [
    [
        'name' => 'name',
        'index' => 'name',
        'width' => '200',
        'align' => 'left',
    ],
    [
        'name' => 'time',
        'index' => 'time',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'progress',
        'index' => 'progress',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'score',
        'index' => 'score',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'works',
        'index' => 'works',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'messages',
        'index' => 'messages',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '50',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

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
'.Display::grid_js(
    'group_users',
    $url,
    $columns,
    $column_model,
    $extra_params,
    [],
    $action_links,
    true
).'
});
</script>';

Display::display_header();

echo '<div class="actions">';
echo TrackingCourseLog::actionsLeft('groups', $sessionId);
echo '</div>';

echo Display::grid_html('group_users');

Display::display_footer();
