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
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : '';

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=document_log_events&'.api_get_cidreq().'&keyword='.$keyword;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Path'),
    get_lang('Name'),
    get_lang('UserName'),
    get_lang('DateEnd'),
    get_lang('Clicks'),
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
        'name' => 'col5',
        'index' => 'col5',
        'width' => '40',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col6',
        'index' => '6',
        'width' => '30',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'col7',
        'index' => '7',
        'width' => '20',
        'align' => 'center',
        'sortable' => 'false',
    ],
    [
        'name' => 'col7',
        'index' => '7',
        'width' => '20',
        'align' => 'center',
        'sortable' => 'false',
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
    'document_log_events',
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
echo TrackingCourseLog::actionsLeft('document_logs', api_get_session_id());
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
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'), 'submit');
echo '<div class="actions">';
$form->display();
echo '</div>';

echo Display::grid_html('document_log_events');

Display::display_footer();
