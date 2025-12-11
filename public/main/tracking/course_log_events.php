<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

$courseId   = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId  = api_get_session_id();

// Access restrictions.
$isAllowedToTrack = Tracking::isAllowToTrack($sessionId);
if (!$isAllowedToTrack) {
    api_not_allowed(true);
}

// Export flags (kept for future use if needed).
$exportCsv = isset($_GET['export']) && 'csv' === $_GET['export'];
$exportXls = isset($_GET['export']) && 'xls' === $_GET['export'];

// Simple keyword filter.
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : '';

// jqGrid URL.
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=course_log_events&'
    .api_get_cidreq().'&keyword='.$keyword;

// Columns.
$columns = [
    get_lang('Event type'),
    get_lang('Data type'),
    get_lang('Value'),
    get_lang('Course'),
    get_lang('Session'),
    get_lang('Username'),
    get_lang('IP address'),
    get_lang('Date'),
];

// Column model.
$columnModel = [
    ['name' => 'col0', 'index' => 'col0', 'width' => '130', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'col1', 'index' => 'col1', 'width' => '110', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'col2', 'index' => 'col2', 'width' => '220', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'col3', 'index' => 'col3', 'width' => '80',  'align' => 'left', 'sortable' => 'false', 'hidden' => 'true'],
    ['name' => 'col4', 'index' => 'col4', 'width' => '80',  'align' => 'left', 'sortable' => 'false', 'hidden' => 'true'],
    ['name' => 'col5', 'index' => 'col5', 'width' => '110', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'col6', 'index' => 'col6', 'width' => '120', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'col7', 'index' => 'col7', 'width' => '130', 'align' => 'left'],
];

// Grid extra params.
$extraParams = [
    'autowidth'   => 'true',
    'height'      => 'auto',
    'sortorder'   => 'desc',
    'sortname'    => 'col7',
    'rowNum'      => 20,
    'rowList'     => [10, 20, 50, 100],
    'viewrecords' => true,
];

$actionLinks = '';

// jqGrid init.
$htmlHeadXtra[] = '
<script>
$(function() {
'.Display::grid_js(
        'course_log_events',
        $url,
        $columns,
        $columnModel,
        $extraParams,
        [],
        $actionLinks,
        true
    ).'
});
</script>';

// -----------------------------------------------------------------------------
// Page rendering
// -----------------------------------------------------------------------------
$pageTitle = get_lang('Course log events');

// Use "Tracking" context so breadcrumbs/icons match other tracking pages.
Display::display_header($pageTitle, 'Tracking');

// Primary My Space menu (top-level reporting navigation).
$primaryMenu = Display::mySpaceMenu('exams');

// Secondary navigation (course / session tracking tabs).
$secondaryMenu = TrackingCourseLog::actionsLeft('logs', $sessionId);

// Simple keyword search form (inline).
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
$form->setDefaults(['keyword' => $keyword]);
$form->addButtonSearch(get_lang('Search'), 'submit');

// Main wrapper with modern spacing.
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-4">';

// Row 1: primary My Space menu.
echo '  <div class="flex flex-wrap gap-2">';
echo        $primaryMenu;
echo '  </div>';

// Row 2: secondary toolbar (tabs + search box).
echo '  <div class="course-log-events-toolbar flex flex-wrap items-center gap-2">';
echo        $secondaryMenu;
echo '      <div class="ml-auto">';
$form->display();
echo '      </div>';
echo '  </div>';

// Row 3: main card with title + grid.
echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-50 overflow-x-auto">';
echo '      <div class="flex items-center gap-2 px-4 pt-4">';
echo            Display::getMdiIcon(
    'file-document-outline',
    'ch-tool-icon text-gray-400',
    null,
    ICON_SIZE_MEDIUM,
    $pageTitle
);
echo '          <h1 class="h4 mb-0">'.$pageTitle.'</h1>';
echo '      </div>';
echo '      <div class="p-4">';
echo            Display::grid_html('course_log_events');
echo '      </div>';
echo '  </section>';

echo '</div>';

Display::display_footer();
