<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_TRACKING;

$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

// Export flags.
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];
$exportXls  = isset($_GET['export']) && 'xls' === $_GET['export'];

$session_id = api_get_session_id();

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    $from_myspace = true;
    $this_section = 'session_my_space';
}

// -----------------------------------------------------------------------------
// Access restrictions
// -----------------------------------------------------------------------------
$is_allowedToTrack = Tracking::isAllowToTrack($session_id);
if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

// -----------------------------------------------------------------------------
// Exports (CSV / XLS)
// -----------------------------------------------------------------------------
if ($export_csv || $exportXls) {
    // Get all resource events without pagination.
    $csvData = TrackingCourseLog::getItemResourcesData(0, 0, '', '');

    // Clean HTML and internal columns before export.
    array_walk(
        $csvData,
        function (&$item) {
            // Remove HTML tags and convert <br> to new lines on value column.
            $item[0] = strip_tags($item[0]);
            $item[2] = strip_tags(preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $item[2]));
            $item[3] = strip_tags($item[3]);
            $item[4] = strip_tags($item[4]);

            // Remove internal columns that should not be exported.
            unset(
                $item['col0'],
                $item['col1'],
                $item['ref'],
                $item['col3'],
                $item['col6'],
                $item['user_id'],
                $item['col7']
            );
        }
    );

    // Add headers row.
    array_unshift(
        $csvData,
        [
            get_lang('Tool'),
            get_lang('Event type'),
            get_lang('Session'),
            get_lang('Username'),
            get_lang('IP address'),
            get_lang('Document'),
            get_lang('Date'),
        ]
    );

    if ($export_csv) {
        Export::arrayToCsv($csvData);
    }

    if ($exportXls) {
        Export::arrayToXls($csvData);
    }

    exit;
}

if (empty($session_id)) {
    $session_id = api_get_session_id();
}

// -----------------------------------------------------------------------------
// Breadcrumbs
// -----------------------------------------------------------------------------
if (isset($_GET['origin']) && 'resume_session' === $_GET['origin']) {
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
    ];
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'session/session_list.php',
        'name' => get_lang('Session list'),
    ];
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.api_get_session_id(),
        'name' => get_lang('Session overview'),
    ];
}

// -----------------------------------------------------------------------------
// Page header & menus
// -----------------------------------------------------------------------------
$nameTools = get_lang('Reporting');

// Use "Tracking" context so global tracking menu is displayed.
Display::display_header($nameTools, 'Tracking');

// Primary tracking menu (main navigation: learners, groups, resources, etc.).
$primaryMenu = Display::mySpaceMenu('exams');

// -----------------------------------------------------------------------------
// Secondary toolbar: course/session tracking tabs + actions (print / export)
// -----------------------------------------------------------------------------
$left = TrackingCourseLog::actionsLeft('resources', api_get_session_id(), false);

$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

$addional_param = '';
if (isset($_GET['additional_profile_field'])) {
    $addional_param = 'additional_profile_field='.intval($_GET['additional_profile_field']);
}

$users_tracking_per_page = '';
if (isset($_GET['users_tracking_per_page'])) {
    $users_tracking_per_page = '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
}

// CSV export icon.
$actionsRight .= Display::url(
    Display::getMdiIcon(
        ActionIcon::EXPORT_CSV,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('CSV export')
    ),
    api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.$users_tracking_per_page
);

// XLS export icon.
$actionsRight .= Display::url(
    Display::getMdiIcon(
        ActionIcon::EXPORT_SPREADSHEET,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Excel export')
    ),
    api_get_self().'?'.api_get_cidreq().'&export=xls&'.$addional_param.$users_tracking_per_page
);

// ToolbarAction renders the secondary tabs (users / groups / resources / ...)
// and the right-side action icons.
$toolbar = Display::toolbarAction('log_resource', [$left, $actionsRight]);

// -----------------------------------------------------------------------------
// Search box (simple keyword filter)
// -----------------------------------------------------------------------------
$form = new FormValidator(
    'search_simple',
    'GET',
    api_get_path(WEB_CODE_PATH).'tracking/course_log_resources.php?'.api_get_cidreq().'&id_session='.$session_id,
    '',
    ['class' => 'form-search'],
    false
);
$renderer = $form->defaultRenderer();
$renderer->setCustomElementTemplate('<span>{element}</span>');
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addElement('hidden', 'cidReq', api_get_course_id());
$form->addElement('hidden', 'id_session', $session_id);
$form->addButtonSearch(get_lang('Search users'), 'submit');
$searchFormHtml = $form->returnForm();

// -----------------------------------------------------------------------------
// Main table (resources log)
// -----------------------------------------------------------------------------
$table = new SortableTable(
    'resources',
    ['TrackingCourseLog', 'countItemResources'],
    ['TrackingCourseLog', 'getItemResourcesData'],
    6,
    20,
    'DESC'
);

$parameters = [
    'id_session' => $session_id,
    'cidReq'     => api_get_course_id(),
];

$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Tool'));
$table->set_header(1, get_lang('Event type'));
$table->set_header(2, get_lang('Session'), false);
$table->set_header(3, get_lang('Username'), true, 'width=65px');
$table->set_header(4, get_lang('IP address'), true, 'width=100px');
$table->set_header(5, get_lang('Document'), false);
$table->set_header(6, get_lang('Date'), true, 'width=190px');

$tableHtml = $table->return_table();

// -----------------------------------------------------------------------------
// Layout (aligned with exams.php: flex rows + cards)
// -----------------------------------------------------------------------------
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-4">';

// Row 1: primary My Space menu.
echo '  <div class="flex flex-wrap gap-2">';
echo        $primaryMenu;
echo '  </div>';

// Row 2: secondary toolbar (local tracking tabs + actions).
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Row 3: search form in a small card.
echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-50">';
echo '      <div class="p-4 md:p-5">';
echo '          <h2 class="text-base font-semibold mb-3">'.get_lang('Search in resource logs').'</h2>';
echo            $searchFormHtml;
echo '      </div>';
echo '  </section>';

// Row 4: resources table card.
echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-50 overflow-x-auto">';
echo        $tableHtml;
echo '  </section>';

echo '</div>';

Display::display_footer();
