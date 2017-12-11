<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.tracking
 */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

// Access restrictions.
$is_allowedToTrack = api_is_platform_admin() || api_is_allowed_to_create_course() || api_is_session_admin() || api_is_drh() || api_is_course_tutor();

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$exportXls = isset($_GET['export']) && $_GET['export'] == 'xls' ? true : false;

$course_id = api_get_course_int_id();
$course_code = api_get_course_id();
$sessionId = api_get_session_id();

$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : '';

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=course_log_events&'.api_get_cidreq().'&keyword='.$keyword;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('EventType'),
    get_lang('DataType'),
    get_lang('Value'),
    get_lang('Course'),
    get_lang('Session'),
    get_lang('UserName'),
    get_lang('IPAddress'),
    get_lang('Date')
);

// Column config
$column_model = array(
    array(
        'name' => 'col0',
        'index' => 'col0',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false'
    ),
    array(
        'name' => 'col1',
        'index' => 'col1',
        'width' => '60',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'col2',
        'index' => 'col2',
        'width' => '200',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'col3',
        'index' => 'col3',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
        'hidden' => 'true'
    ),
    array(
        'name' => 'col4',
        'index' => 'col4',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
        'hidden' => 'true'
    ),
    array(
        'name' => 'col5',
        'index' => 'col5',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'col6',
        'index' => '6',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ),
    array(
        'name' => 'col7',
        'index' => '7',
        'width' => '50',
        'align' => 'left'
    )
);

// Autowidth
$extra_params['autowidth'] = 'true';
// height auto
$extra_params['height'] = 'auto';
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
    array(),
    $actionLinks,
    true
).'
});
</script>';

Display::display_header();

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('user.png', get_lang('StudentsTracking'), array(), ICON_SIZE_MEDIUM),
    'courseLog.php?'.api_get_cidreq(true, false)
);
echo Display::url(Display::return_icon('group.png', get_lang('GroupReporting'), array(), ICON_SIZE_MEDIUM), '#');
echo Display::url(
    Display::return_icon('course.png', get_lang('CourseTracking'), array(), ICON_SIZE_MEDIUM),
    'course_log_tools.php?'.api_get_cidreq(true, false)
);
echo Display::url(
    Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), ICON_SIZE_MEDIUM),
    'course_log_resources.php?'.api_get_cidreq(true, false)
);
echo '</div>';

$form = new FormValidator(
    'search_simple',
    'get',
    api_get_self().'?'.api_get_cidreq(),
    '',
    [],
    FormValidator::LAYOUT_INLINE
);
$form->addHidden('report', 'activities');
$form->addHidden('activities_direction', 'DESC');
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'), 'submit');
echo '<div class="actions">';
$form->display();
echo '</div>';

echo Display::grid_html('course_log_events');

Display::display_footer();
