<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$fromMySpace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    // When the page is opened from "My space > Reporting".
    $fromMySpace = true;
    $this_section = 'session_my_space';
}

// -----------------------------------------------------------------------------
// Access restrictions
// -----------------------------------------------------------------------------
$isAllowedToTrack = Tracking::isAllowToTrack($sessionId);
if (!$isAllowedToTrack) {
    api_not_allowed(true);
}

$nameTools = get_lang('Group reporting');

// jqGrid will use this URL to do the selects.
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_group_reporting&course_id='.
    $courseId.'&session_id='.$sessionId.'&'.api_get_cidreq();

// The order is important; you need to check the $columns variable
// in the model.ajax.php file.
$columns = [
    get_lang('Title'),
    get_lang('Time'),
    get_lang('Progress'),
    get_lang('Score'),
    get_lang('Assignments'),
    get_lang('Messages'),
    get_lang('Detail'),
];

// Column config.
$column_model = [
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '200',
        'align' => 'left',
    ],
    [
        'name' => 'time',
        'index' => 'time',
        'width' => '70',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'progress',
        'index' => 'progress',
        'width' => '80',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'score',
        'index' => 'score',
        'width' => '80',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'works',
        'index' => 'works',
        'width' => '80',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'messages',
        'index' => 'messages',
        'width' => '80',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '70',
        'align' => 'center',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

// jqGrid extra params (visual tweaks only).
$extra_params = [
    'autowidth' => 'true',
    'height' => 'auto',
    'rowNum' => 20,
    'rowList' => [10, 20, 50, 100],
    'viewrecords' => true,
    'gridview' => true,
    'shrinkToFit' => true,
];

$action_links = '
function action_formatter(cellvalue, options, rowObject) {
    return \'<a href="course_log_tools.php?id_session=0&cid='.$courseId.'&gid=\'
        + options.rowId +
        \'">'.Display::getMdiIcon('chevron-double-right', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>\';
}';

// jqGrid JS init.
$htmlHeadXtra[] = '
<script>
$(function() {
' . Display::grid_js(
        'group_users',
        $url,
        $columns,
        $column_model,
        $extra_params,
        [],
        $action_links,
        true
    ) . '

    // Force grid to use the full width of the card container
    var $grid = $("#group_users");

    function resizeGroupGrid() {
        var $card = $("#group-reporting-wrapper .card");
        if ($card.length && $grid.length && $grid.closest(".ui-jqgrid").length) {
            var newWidth = $card.innerWidth() - 2; // small padding fix
            $grid.jqGrid("setGridWidth", newWidth, true);
        }
    }

    resizeGroupGrid();
    $(window).on("resize", resizeGroupGrid);
});
</script>';

// -----------------------------------------------------------------------------
// Page rendering
// -----------------------------------------------------------------------------
Display::display_header($nameTools, 'Tracking');

// Primary reporting menu (My space) – same row of big icons as in exams.php.
$primaryMenu = Display::mySpaceMenu('exams');

// Secondary navigation: tracking tabs (users, groups, resources, etc.).
$secondaryMenu = TrackingCourseLog::actionsLeft('groups', $sessionId);

// Wrap secondary navigation inside a toolbar so it behaves like horizontal tabs.
$toolbar = Display::toolbarAction('toolbar-groups', [$secondaryMenu]);

// Panel title for the grid.
$panelTitle = Display::getMdiIcon(
        'account-group',
        'ch-tool-icon',
        null,
        ICON_SIZE_SMALL,
        get_lang('Group reporting')
    ).' '.get_lang('Group reporting');

// Main layout wrapper.
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-4">';

// Row 1: primary My space menu.
echo '  <div class="flex flex-wrap gap-2">';
echo        $primaryMenu;
echo '  </div>';

// Row 2: secondary tracking tabs.
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Row 3: group statistics grid.
echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-50 overflow-x-auto">';
echo '      <div id="group-reporting-wrapper">';
echo            Display::panel(Display::grid_html('group_users'), $panelTitle);
echo '      </div>';
echo '  </section>';

echo '</div>';

Display::display_footer();
