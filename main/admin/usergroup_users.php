<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$usergroup = new UserGroup();
$userGroupInfo = $usergroup->get($id);
if (empty($userGroupInfo)) {
    api_not_allowed(true);
}

$usergroup->protectScript($userGroupInfo, true, true);
$allowEdit = api_is_platform_admin() || isset($userGroupInfo['author_id']) && $userGroupInfo['author_id'] == api_get_user_id();

$calendarPlugin = null;
if ($allowEdit && api_get_plugin_setting('learning_calendar', 'enabled') === 'true') {
    $calendarPlugin = LearningCalendarPlugin::create();
}

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$calendarId = isset($_REQUEST['calendar_id']) ? (int) $_REQUEST['calendar_id'] : 0;

$courseInfo = api_get_course_info();
if (empty($courseInfo)) {
    $interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];
} else {
    $interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'user/class.php?'.api_get_cidreq(), 'name' => get_lang('Classes')];
}
$interbreadcrumb[] = ['url' => '#', 'name' => $userGroupInfo['name']];

if (!empty($action)) {
    $usergroup->protectScript($userGroupInfo);
}

switch ($action) {
    case 'add_calendar':
        $form = new FormValidator(
            'add_calendar',
            'post',
            api_get_self().'?id='.$id.'&user_id='.$userId.'&action=add_calendar'
        );

        $userInfo = api_get_user_info($userId);
        $form->addHeader($userInfo['complete_name']);
        $calendarPlugin->getAddUserToCalendarForm($form);
        $form->addButtonSave(get_lang('Add'));
        $form->display();

        if ($form->validate()) {
            $calendarId = $form->getSubmitValue('calendar_id');
            if (!empty($calendarId)) {
                $calendarPlugin->addUserToCalendar($calendarId, $userId);
                Display::addFlash(Display::return_message(get_lang('Added'), 'confirmation'));
                header('Location: '.api_get_self().'?id='.$id);
                exit;
            }
        }
        exit;
        break;
    case 'edit_calendar':
        $form = new FormValidator(
            'add_calendar',
            'post',
            api_get_self().'?id='.$id.'&user_id='.$userId.'&action=edit_calendar&calendar_id='.$calendarId
        );
        $userInfo = api_get_user_info($userId);
        $form->addHeader($userInfo['complete_name']);
        $calendarPlugin->getAddUserToCalendarForm($form);
        $form->setDefaults(['calendar_id' => $calendarId]);
        $form->addButtonSave(get_lang('Update'));
        $form->display();

        if ($form->validate()) {
            $calendarId = $form->getSubmitValue('calendar_id');
            if (!empty($calendarId)) {
                $calendarPlugin->updateUserToCalendar($calendarId, $userId);
                Display::addFlash(Display::return_message(get_lang('Added'), 'confirmation'));
                header('Location: '.api_get_self().'?id='.$id);
                exit;
            }
        }
        exit;
        break;
    case 'delete':
        $res = $usergroup->delete_user_rel_group($_GET['user_id'], $_GET['id']);
        Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
        header('Location: '.api_get_self().'?id='.$id);
        exit;
        break;
    case 'create_control_point':
        $value = isset($_GET['value']) ? (int) $_GET['value'] : 0;
        $calendarPlugin->addControlPoint($userId, $value);
        Display::addFlash(
            Display::return_message($calendarPlugin->get_lang('ControlPointAdded'), 'confirmation')
        );
        header('Location: '.api_get_self().'?id='.$id);
        exit;
    case 'add_multiple_users_to_calendar':
        $userList = isset($_REQUEST['user_list']) ? explode(',', $_REQUEST['user_list']) : 0;
        foreach ($userList as $userId) {
            $isAdded = $calendarPlugin->addUserToCalendar($calendarId, $userId);
            if (!$isAdded) {
                $isAdded = $calendarPlugin->updateUserToCalendar($calendarId, $userId);
            }
        }

        Display::addFlash(
            Display::return_message(get_lang('Added'), 'confirmation')
        );

        header('Location: '.api_get_self().'?id='.$id);
        exit;
        break;
}

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

if ($calendarPlugin) {
    $columns = [
        get_lang('Name'),
        get_lang('Calendar'),
        get_lang('ClassroomActivity'),
        get_lang('TimeSpentByStudentsInCourses'),
        $calendarPlugin->get_lang('NumberDaysAccumulatedInCalendar'),
        $calendarPlugin->get_lang('DifferenceOfDaysAndCalendar'),
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
            'name' => 'gradebook_items',
            'index' => 'gradebook_items',
            'width' => '35',
            'align' => 'left',
            'sortable' => 'false',
        ],
        ['name' => 'time_spent', 'index' => 'time_spent', 'width' => '35', 'align' => 'left', 'sortable' => 'false'],
        [
            'name' => 'lp_day_completed',
            'index' => 'lp_day_completed',
            'width' => '35',
            'align' => 'left',
            'sortable' => 'false',
        ],
        ['name' => 'days_diff', 'index' => 'days_diff', 'width' => '35', 'align' => 'left', 'sortable' => 'false'],
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
$extraParams['multiselect'] = $allowEdit;

$deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), null, ICON_SIZE_SMALL);
$urlStats = api_get_path(WEB_CODE_PATH);

$reportingIcon = Display::return_icon('statistics.png', get_lang('Reporting'), '', ICON_SIZE_SMALL);
$controlPoint = Display::return_icon('add.png', get_lang('ControlPoint'), '', ICON_SIZE_SMALL);

$link = '';

if ($calendarPlugin) {
    $link = '<a href="'.$urlStats.'admin/usergroup_users.php?action=create_control_point&value=\'+value+\'&id='.$id.'&user_id=\'+options.rowId+\'">'.$controlPoint.'</a>';
}

$deleteButton = '';
if ($allowEdit) {
    $deleteButton = '<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."\'".')) return false;" href="?id='.$id.'&action=delete&user_id=\'+options.rowId+\'">'.$deleteIcon.'</a>';
}
//return \'<a href="session_edit.php?page=resume_session.php&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
// With this function we can add actions to the jgrid
$action_links = '
function action_formatter(cellvalue, options, rowObject) {
    var value = rowObject[5];
    return \''.
    '&nbsp;'.$link.
    '&nbsp;<a href="'.$urlStats.'mySpace/myStudents.php?student=\'+options.rowId+\'">'.$reportingIcon.'</a>'.
    ' '.$deleteButton.' \';
}

function extra_formatter(cellvalue, options, rowObject) {
    var calendarName = rowObject[1];
    var calendarId = rowObject[7];

    if (calendarName == "") {
        return \'<a href="'.
        api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?user_id=\'+options.rowId+\'&id='.$id.'&action=add_calendar&width=700" class="btn btn-primary ajax">'.get_lang('Add').'</a>\';
    } else {
    return \' \'+calendarName+\' <a href="'.
        api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?calendar_id=\'+calendarId+\'&user_id=\'+options.rowId+\'&id='.$id.'&action=edit_calendar&width=700" class="btn btn-primary ajax"> '.get_lang('Edit').'</a>\';
    }

    return calendarName;

    return \''.
    '&nbsp;<a href="'.$urlStats.'mySpace/myStudents.php?student=\'+options.rowId+\'">'.Display::return_icon('statistics.png', get_lang('Reporting'), '', ICON_SIZE_SMALL).'</a>'.
    ' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?id='.$id.'&action=delete&user_id=\'+options.rowId+\'">'.$deleteIcon.'</a>\';
}';

$deleteUrl = api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?a=delete_user_in_usergroup&group_id='.$id;

if ($calendarPlugin) {
    $form = new FormValidator(
        'add_multiple_calendar',
        'post',
        api_get_self().'?id='.$id.'&action=add_multiple_users_to_calendar'
    );
    $calendarPlugin->getAddUserToCalendarForm($form);
    $form->addHidden('user_list', '');
    $form->addButtonSave(get_lang('Add'));
}

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
        { edit: false, add: false, del: <?php echo $allowEdit ? 'true' : 'false'; ?>, search: false},
        { height:280, reloadAfterSubmit:false }, // edit options
        { height:280, reloadAfterSubmit:false }, // add options
        { reloadAfterSubmit:false, url: "<?php echo $deleteUrl; ?>" }, // del options
        { width:500 } // search options
    )
    <?php if ($calendarPlugin) { ?>
    .navButtonAdd('#usergroups_pager',{
        caption:"<?php echo addslashes($calendarPlugin->get_lang('UpdateCalendar')); ?>",
        buttonicon:"ui-icon ui-icon-plus",
        onClickButton: function(a) {
            var userIdList = $("#usergroups").jqGrid('getGridParam', 'selarrrow');
            if (userIdList.length) {
                $(".modal-body #add_multiple_calendar_user_list").val(userIdList);
                $('#myModal').modal();
            } else {
                alert("<?php echo addslashes(get_lang('SelectStudents')); ?>");
            }
        },
        position:"last"
    })
    <?php } ?>
    ;
});
</script>
<?php if ($calendarPlugin) { ?>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <?php echo $calendarPlugin->get_lang('AddMultipleUsersToCalendar'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <?php echo $form->display(); ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>
<?php

$usergroup->showGroupTypeSetting = true;
// Action handling: Adding a note
if ($allowEdit && $action === 'delete' && is_numeric($_GET['id'])) {
    $res = $usergroup->delete_user_rel_group($_GET['user_id'], $_GET['id']);
    Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
    header('Location: '.api_get_self().'?id='.$id);
    exit;
}

$usergroup->displayToolBarUserGroupUsers();

Display::display_footer();
