<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

$course_id = api_get_course_int_id();
$course_code = api_get_course_id();
$sessionId = api_get_session_id();

// Access restrictions.
$is_allowedToTrack = Tracking::isAllowToTrack($sessionId);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && 'csv' == $_GET['export'] ? true : false;
$exportXls = isset($_GET['export']) && 'xls' == $_GET['export'] ? true : false;
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : '';

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=course_log_events&'.api_get_cidreq().'&keyword='.$keyword;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('EventType'),
    get_lang('DataType'),
    get_lang('Value'),
    get_lang('Course'),
    get_lang('Session'),
    get_lang('UserName'),
    get_lang('IPAddress'),
    get_lang('Date'),
];

// Column config
$column_model = [
    [
        'name' => 'col0',
        'index' => 'col0',
        'width' => '70',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col1',
        'index' => 'col1',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col2',
        'index' => 'col2',
        'width' => '200',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col3',
        'index' => 'col3',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
        'hidden' => 'true',
    ],
    [
        'name' => 'col4',
        'index' => 'col4',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
        'hidden' => 'true',
    ],
    [
        'name' => 'col5',
        'index' => 'col5',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col6',
        'index' => '6',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col7',
        'index' => '7',
        'width' => '50',
        'align' => 'left',
    ],
];

// Autowidth
$extra_params['autowidth'] = 'true';
// height auto
$extra_params['height'] = 'auto';

// Order by date
$extra_params['sortorder'] = 'desc';
$extra_params['sortname'] = 'col7';

$actionLinks = '';

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = '
<script>
$(function() {
'.Display::grid_js(
    'course_log_events',
    $url,
    $columns,
    $column_model,
    $extra_params,
    [],
    $actionLinks,
    true
).'
});
</script>';

Display::display_header();

echo '<div class="actions">';
echo TrackingCourseLog::actionsLeft('logs', api_get_session_id());

echo '</div>';

$form = new FormValidator(
    'search_simple',
    'get',
    api_get_self().'?'.api_get_cidreq(),
    '',
    [],
    FormValidator::LAYOUT_INLINE
);
$renderer = $form->defaultRenderer();
$renderer->setCustomElementTemplate('<span>{element}</span>');
$form->addHidden('report', 'activities');
$form->addHidden('activities_direction', 'DESC');
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'), 'submit');
echo '<div class="actions">';
$form->display();
echo '</div>';

echo Display::grid_html('course_log_events');

Display::display_footer();
