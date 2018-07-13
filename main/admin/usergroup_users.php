<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$usergroup = new UserGroup();

$usergroup->protectScript();

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$calendarId = isset($_GET['calendar_id']) ? (int) $_GET['calendar_id'] : 0;

$userGroupInfo = $usergroup->get($id);

if (empty($userGroupInfo)) {
    api_not_allowed(true);
}

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];
$interbreadcrumb[] = ['url' => '#', 'name' => $userGroupInfo['name']];

switch ($action) {
    case 'add_calendar':
        $calendars = LpCalendarPlugin::getCalendars(0, 1000, '');
        if (empty($calendars)) {
            echo Display::return_message(get_lang('Nodata'));
            exit;
        }
        $userInfo = api_get_user_info($userId);

        $calendars = array_column($calendars, 'title', 'id');
        $calendars = array_map('strip_tags', $calendars);

        $form = new FormValidator(
            'add_calendar',
            'post',
            api_get_self().'?id='.$id.'&user_id='.$userId.'&action=add_calendar'
        );
        $form->addHeader($userInfo['complete_name']);
        $form->addSelect('calendar_id', get_lang('Calendar'), $calendars, ['disable_js' => true]);
        $form->addButtonSave(get_lang('Save'));
        $form->display();

        if ($form->validate()) {
            $calendarId = $form->getSubmitValue('calendar_id');
            if (!empty($calendarId)) {
                LpCalendarPlugin::addUserToCalendar($calendarId, $userId);
                Display::addFlash(Display::return_message(get_lang('Added'), 'confirmation'));
                header('Location: '.api_get_self().'?id='.$id);
                exit;
            }
        }
        exit;
        break;
    case 'edit_calendar':
        $calendars = LpCalendarPlugin::getCalendars(0, 1000, '');
        if (empty($calendars)) {
            echo Display::return_message(get_lang('Nodata'));
            exit;
        }

        $calendars = array_column($calendars, 'title', 'id');
        $calendars = array_map('strip_tags', $calendars);

        $form = new FormValidator(
            'add_calendar',
            'post',
            api_get_self().'?id='.$id.'&user_id='.$userId.'&action=edit_calendar&calendar_id='.$calendarId
        );
        $userInfo = api_get_user_info($userId);
        $form->addHeader($userInfo['complete_name']);
        $form->addSelect('calendar_id', get_lang('Calendar'), $calendars, ['disable_js' => true]);
        $form->addButtonSave(get_lang('Update'));
        $form->setDefaults(['calendar_id' => $calendarId]);
        $form->display();

        if ($form->validate()) {
            $calendarId = $form->getSubmitValue('calendar_id');
            if (!empty($calendarId)) {
                LpCalendarPlugin::updateUserToCalendar($calendarId, $userId);
                Display::addFlash(Display::return_message(get_lang('Added'), 'confirmation'));
                header('Location: '.api_get_self().'?id='.$id);
                exit;
            }
        }
        exit;
        break;
    case 'delete':
        $res = $usergroup->delete_user_rel_group($_GET['user_id'], $_GET['id']);

        //LpCalendarPlugin::deleteAllCalendarFromUser();

        Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
        header('Location: '.api_get_self().'?id='.$id);
        exit;
        break;
}

// The header.
Display::display_header();

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups_users&id='.$id;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('Actions'),
];

// Column config
$column_model = [
    ['name' => 'name', 'index' => 'name', 'width' => '35', 'align' => 'left', 'sortable' => 'false'],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '20',
        'align' => 'center',
        'sortable' => 'false',
        'formatter' => 'action_formatter',
    ],
];


if (api_get_plugin_setting('lp_calendar', 'enabled') === 'true') {
    $columns = [
        get_lang('Name'),
        get_lang('Calendar'),
        get_lang('Actions'),
    ];

    // Column config
    $column_model = [
        ['name' => 'name', 'index' => 'name', 'width' => '35', 'align' => 'left', 'sortable' => 'false'],
        [
            'name' => 'calendar',
            'index' => 'calendar',
            'width' => '35',
            'align' => 'left',
            'sortable' => 'false',
            'formatter' => 'extra_formatter',
        ],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '20',
            'align' => 'center',
            'sortable' => 'false',
            'formatter' => 'action_formatter',
        ],
    ];
}

// Autowidth
$extraParams['autowidth'] = 'true';
// height auto
$extraParams['height'] = 'auto';
$extraParams['sortname'] = 'name';
$extraParams['sortorder'] = 'desc';
$extraParams['multiselect'] = true;

$deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), null, ICON_SIZE_SMALL);
$urlStats = api_get_path(WEB_CODE_PATH);

//$addCalendar = '<a href="'.$urlStats.'mySpace/myStudents.php?student=\'+options.rowId+\'">'.Display::return_icon('agenda.png', get_lang('Agenda'), '', ICON_SIZE_SMALL).'</a>';

//return \'<a href="session_edit.php?page=resume_session.php&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
// With this function we can add actions to the jgrid
$action_links = '
function action_formatter(cellvalue, options, rowObject) {
    return \''.
    '&nbsp;<a href="'.$urlStats.'mySpace/myStudents.php?student=\'+options.rowId+\'">'.Display::return_icon('stats.png', get_lang('Reporting'), '', ICON_SIZE_SMALL).'</a>'.
    ' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?id='.$id.'&action=delete&user_id=\'+options.rowId+\'">'.$deleteIcon.'</a>\';
}

function extra_formatter(cellvalue, options, rowObject) {
    var calendarName = rowObject[1];
    var calendarId = rowObject[3];
    
    if (calendarName == "") {          
        return \'<a href="'.
        api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?user_id=\'+options.rowId+\'&id='.$id.'&action=add_calendar&width=700" class="btn btn-primary ajax">'.get_lang('Add').'</a>\';
    } else {
    return \' \'+calendarName+\' <a href="'.
        api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?calendar_id=\'+calendarId+\'&user_id=\'+options.rowId+\'&id='.$id.'&action=edit_calendar&width=700" class="btn btn-primary ajax"> '.get_lang('Edit').'</a>\';
    }

    return calendarName;

    return \''.
    '&nbsp;<a href="'.$urlStats.'mySpace/myStudents.php?student=\'+options.rowId+\'">'.Display::return_icon('stats.png', get_lang('Reporting'), '', ICON_SIZE_SMALL).'</a>'.
    ' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?id='.$id.'&action=delete&user_id=\'+options.rowId+\'">'.$deleteIcon.'</a>\';
}

';

$deleteUrl = api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?a=delete_user_in_usergroup&group_id='.$id;

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

$usergroup->showGroupTypeSetting = true;
// Action handling: Adding a note
if ($action === 'delete' && is_numeric($_GET['id'])) {
    $res = $usergroup->delete_user_rel_group($_GET['user_id'], $_GET['id']);
    Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
    header('Location: '.api_get_self().'?id='.$id);
    exit;
} else {
    $usergroup->displayToolBarUserGroupUsers();
}

Display::display_footer();
