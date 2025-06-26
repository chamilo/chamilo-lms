<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../../main/inc/global.inc.php';

$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$plugin = LearningCalendarPlugin::create();
$plugin->protectCalendar($calendarId);
$item = $plugin->getCalendar($calendarId);

if (empty($item)) {
    api_not_allowed(true);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$formToString = '';
$template = new Template();
$actionLeft = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/start.php'
);

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_calendar_users&id='.$calendarId;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('First name'),
    get_lang('Last name'),
    get_lang('Exam'),
];

// Column config
$column_model = [
    ['name' => 'firstname', 'index' => 'firstname', 'width' => '35', 'align' => 'left', 'sortable' => 'false'],
    ['name' => 'lastname', 'index' => 'lastname', 'width' => '35', 'align' => 'left', 'sortable' => 'false'],
    [
        'name' => 'exam',
        'index' => 'exam',
        'width' => '20',
        'align' => 'center',
        'sortable' => 'false',
    ],
];

// Autowidth
$extraParams['autowidth'] = 'true';
// height auto
$extraParams['height'] = 'auto';
$extraParams['sortname'] = 'title';
$extraParams['sortorder'] = 'desc';
$extraParams['multiselect'] = true;

$deleteIcon = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'));
$urlStats = api_get_path(WEB_CODE_PATH);
$action_links = '';
$deleteUrl = '';

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

Display::display_header();

?>
    <script>
        $(function() {
            <?php
            // grid definition see the $usergroup>display() function
            echo Display::grid_js(
                'usergroups',
                $url,
                $columns,
                $column_model,
                $extraParams,
                [],
                $action_links,
                true
            );
            ?>
            $("#usergroups").jqGrid(
                "navGrid",
                "#usergroups_pager",
                { edit: false, add: false, del: true, search: false},
                { height:280, reloadAfterSubmit:false }, // edit options
                { height:280, reloadAfterSubmit:false }, // add options
                { reloadAfterSubmit:false, url: "<?php echo $deleteUrl; ?>" }, // del options
                { width:500 } // search options
            );
        });
    </script>
<?php

// action links
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?id='.$calendarId.'">'.
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Administration')).
    '</a>';
echo '</div>';
echo Display::grid_html('usergroups');

Display::display_footer();
