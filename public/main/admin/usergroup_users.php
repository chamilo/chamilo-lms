<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

require_once __DIR__.'/../inc/global.inc.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$usergroup = new UserGroupModel();
$userGroupInfo = $usergroup->get($id);
if (empty($userGroupInfo)) {
    api_not_allowed(true);
}

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : null;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$calendarId = isset($_REQUEST['calendar_id']) ? (int) $_REQUEST['calendar_id'] : 0;

$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$courseId = !empty($courseInfo) ? (int) $courseInfo['real_id'] : 0;
$cidReq = !empty($courseInfo) ? '&'.api_get_cidreq() : '';

$this_section = empty($courseInfo) ? SECTION_PLATFORM_ADMIN : SECTION_COURSES;

$canViewFromCourse = false;

if (!empty($courseInfo) && api_is_allowed_to_edit()) {
    if ($sessionId > 0) {
        $table = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
        $sql = "SELECT session_id
                FROM $table
                WHERE usergroup_id = $id AND session_id = $sessionId
                LIMIT 1";
        $result = Database::query($sql);
        $canViewFromCourse = Database::num_rows($result) > 0;
    } else {
        $table = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $sql = "SELECT course_id
                FROM $table
                WHERE usergroup_id = $id AND course_id = $courseId
                LIMIT 1";
        $result = Database::query($sql);
        $canViewFromCourse = Database::num_rows($result) > 0;
    }
}

if (!$canViewFromCourse) {
    $usergroup->protectScript($userGroupInfo, true, true);
}

$allowEdit = api_is_platform_admin() || (
        isset($userGroupInfo['author_id']) && (int) $userGroupInfo['author_id'] === api_get_user_id()
    );

if (!empty($action) && !$allowEdit) {
    api_not_allowed(true);
}

$calendarPlugin = null;
if ($allowEdit && 'true' === api_get_plugin_setting('learning_calendar', 'enabled')) {
    $calendarPlugin = LearningCalendarPlugin::create();
}

$calendarActions = [
    'add_calendar',
    'edit_calendar',
    'create_control_point',
    'add_multiple_users_to_calendar',
];

if (in_array($action, $calendarActions, true) && null === $calendarPlugin) {
    api_not_allowed(true);
}

if (empty($courseInfo)) {
    $interbreadcrumb[] = [
        'url' => 'usergroups.php',
        'name' => get_lang('Classes'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'user/class.php?'.api_get_cidreq(),
        'name' => get_lang('Classes'),
    ];
}
$interbreadcrumb[] = ['url' => '#', 'name' => $userGroupInfo['title']];

if (!empty($action) && $allowEdit) {
    $usergroup->protectScript($userGroupInfo);
}

switch ($action) {
    case 'add_calendar':
        $form = new FormValidator(
            'add_calendar',
            'post',
            api_get_self().'?id='.$id.$cidReq.'&user_id='.$userId.'&action=add_calendar'
        );

        $userInfo = api_get_user_info($userId);
        $form->addHeader($userInfo['complete_name']);
        $calendarPlugin->getAddUserToCalendarForm($form);
        $form->addButtonSave(get_lang('Add'));

        if ($form->validate()) {
            $calendarId = (int) $form->getSubmitValue('calendar_id');
            if (!empty($calendarId)) {
                $calendarPlugin->addUserToCalendar($calendarId, $userId);
                Display::addFlash(Display::return_message(get_lang('Added'), 'confirmation'));
                header('Location: '.api_get_self().'?id='.$id.$cidReq);
                exit;
            }
        }

        Display::display_header();
        $form->display();
        Display::display_footer();
        exit;

    case 'edit_calendar':
        $form = new FormValidator(
            'add_calendar',
            'post',
            api_get_self().'?id='.$id.$cidReq.'&user_id='.$userId.'&action=edit_calendar&calendar_id='.$calendarId
        );

        $userInfo = api_get_user_info($userId);
        $form->addHeader($userInfo['complete_name']);
        $calendarPlugin->getAddUserToCalendarForm($form);
        $form->setDefaults(['calendar_id' => $calendarId]);
        $form->addButtonSave(get_lang('Update'));

        if ($form->validate()) {
            $calendarId = (int) $form->getSubmitValue('calendar_id');
            if (!empty($calendarId)) {
                $calendarPlugin->updateUserToCalendar($calendarId, $userId);
                Display::addFlash(Display::return_message(get_lang('Added'), 'confirmation'));
                header('Location: '.api_get_self().'?id='.$id.$cidReq);
                exit;
            }
        }

        Display::display_header();
        $form->display();
        Display::display_footer();
        exit;

    case 'delete':
        $usergroup->delete_user_rel_group($userId, $id);
        Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
        header('Location: '.api_get_self().'?id='.$id.$cidReq);
        exit;

    case 'create_control_point':
        $value = isset($_GET['value']) ? (int) $_GET['value'] : 0;
        $calendarPlugin->addControlPoint($userId, $value);
        Display::addFlash(
            Display::return_message($calendarPlugin->get_lang('Control point added'), 'confirmation')
        );
        header('Location: '.api_get_self().'?id='.$id.$cidReq);
        exit;

    case 'add_multiple_users_to_calendar':
        $userList = isset($_REQUEST['user_list']) ? explode(',', $_REQUEST['user_list']) : [];
        foreach ($userList as $selectedUserId) {
            $selectedUserId = (int) $selectedUserId;
            if (empty($selectedUserId)) {
                continue;
            }

            $isAdded = $calendarPlugin->addUserToCalendar($calendarId, $selectedUserId);
            if (!$isAdded) {
                $calendarPlugin->updateUserToCalendar($calendarId, $selectedUserId);
            }
        }

        Display::addFlash(
            Display::return_message(get_lang('Added'), 'confirmation')
        );
        header('Location: '.api_get_self().'?id='.$id.$cidReq);
        exit;
}

Display::display_header();

// jqGrid data source
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups_users&id='.$id.$cidReq;

// The order is important; it must match the model.ajax.php response.
$columns = [
    get_lang('Name'),
    get_lang('Detail'),
];

// Use proportional widths so the grid can stretch naturally.
$column_model = [
    [
        'name' => 'name',
        'index' => 'name',
        'width' => '70',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '30',
        'align' => 'center',
        'sortable' => 'false',
        'formatter' => 'action_formatter',
    ],
];

if ($calendarPlugin) {
    $columns = [
        get_lang('Name'),
        get_lang('Calendar'),
        get_lang('Classroom activity'),
        get_lang('Time spent by students in courses'),
        $calendarPlugin->get_lang('Number of days accumulated in calendar'),
        $calendarPlugin->get_lang('Difference between days and calendar'),
        get_lang('Detail'),
    ];

    $column_model = [
        [
            'name' => 'name',
            'index' => 'name',
            'width' => '28',
            'align' => 'left',
            'sortable' => 'false',
        ],
        [
            'name' => 'calendar',
            'index' => 'calendar',
            'width' => '14',
            'align' => 'left',
            'sortable' => 'false',
            'formatter' => 'extra_formatter',
        ],
        [
            'name' => 'gradebook_items',
            'index' => 'gradebook_items',
            'width' => '12',
            'align' => 'left',
            'sortable' => 'false',
        ],
        [
            'name' => 'time_spent',
            'index' => 'time_spent',
            'width' => '12',
            'align' => 'left',
            'sortable' => 'false',
        ],
        [
            'name' => 'lp_day_completed',
            'index' => 'lp_day_completed',
            'width' => '12',
            'align' => 'left',
            'sortable' => 'false',
        ],
        [
            'name' => 'days_diff',
            'index' => 'days_diff',
            'width' => '10',
            'align' => 'left',
            'sortable' => 'false',
        ],
        [
            'name' => 'actions',
            'index' => 'actions',
            'width' => '12',
            'align' => 'center',
            'sortable' => 'false',
            'formatter' => 'action_formatter',
        ],
    ];
}

$extraParams['autowidth'] = 'true';
$extraParams['height'] = 'auto';
$extraParams['sortname'] = 'name';
$extraParams['sortorder'] = 'desc';
$extraParams['multiselect'] = $allowEdit;
$extraParams['shrinkToFit'] = true;
$extraParams['forceFit'] = true;
$extraParams['viewrecords'] = true;

$deleteIcon = Display::getMdiIcon(
    ActionIcon::DELETE,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL,
    get_lang('Delete')
);

$urlStats = api_get_path(WEB_CODE_PATH);

$reportingIcon = Display::getMdiIcon(
    ToolIcon::TRACKING,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL,
    get_lang('Reporting')
);

$controlPoint = Display::getMdiIcon(
    ActionIcon::ADD,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL,
    get_lang('Control point')
);

$link = '';

if ($calendarPlugin) {
    $link = '<a href="'.$urlStats.'admin/usergroup_users.php?action=create_control_point&value=\'+value+\'&id='.$id.$cidReq.'&user_id=\'+options.rowId+\'">'.$controlPoint.'</a>';
}

$deleteButton = '';
if ($allowEdit) {
    $deleteButton = '<a onclick="javascript:if(!confirm('."\'".addslashes(
            api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)
        )."\'".')) return false;" href="?id='.$id.$cidReq.'&action=delete&user_id=\'+options.rowId+\'">'.$deleteIcon.'</a>';
}

$action_links = '
function action_formatter(cellvalue, options, rowObject) {
    var value = rowObject[5];
    return \''.
    '&nbsp;'.$link.
    '&nbsp;<a href="'.$urlStats.'my_space/myStudents.php?student=\'+options.rowId+\'">'.$reportingIcon.'</a>'.
    ' '.$deleteButton.' \';
}

function extra_formatter(cellvalue, options, rowObject) {
    var calendarName = rowObject[1];
    var calendarId = rowObject[7];

    if (calendarName == "") {
        return \'<a href="'.
    api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?user_id=\'+options.rowId+\'&id='.$id.$cidReq.'&action=add_calendar&width=700" class="btn btn--primary ajax">'.get_lang('Add').'</a>\';
    } else {
        return \' \'+calendarName+\' <a href="'.
    api_get_path(WEB_CODE_PATH).'admin/usergroup_users.php?calendar_id=\'+calendarId+\'&user_id=\'+options.rowId+\'&id='.$id.$cidReq.'&action=edit_calendar&width=700" class="btn btn--secondary ajax"> '.get_lang('Edit').'</a>\';
    }
}';

$deleteUrl = api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?a=delete_user_in_usergroup&group_id='.$id.$cidReq;

if ($calendarPlugin) {
    $form = new FormValidator(
        'add_multiple_calendar',
        'post',
        api_get_self().'?id='.$id.$cidReq.'&action=add_multiple_users_to_calendar'
    );
    $calendarPlugin->getAddUserToCalendarForm($form);
    $form->addHidden('user_list', '');
    $form->addButtonSave(get_lang('Add'));
}
?>

    <style>
        /* Page-specific responsive tweaks for the class members grid */
        .usergroup-users-page {
            width: 100%;
        }

        .usergroup-users-page .ui-jqgrid,
        .usergroup-users-page .ui-jqgrid-view,
        .usergroup-users-page .ui-jqgrid-hdiv,
        .usergroup-users-page .ui-jqgrid-bdiv,
        .usergroup-users-page .ui-jqgrid-sdiv,
        .usergroup-users-page .ui-jqgrid-pager {
            width: 100% !important;
            max-width: none !important;
        }

        .usergroup-users-page .ui-jqgrid .ui-jqgrid-htable,
        .usergroup-users-page .ui-jqgrid .ui-jqgrid-btable {
            width: 100% !important;
        }

        .usergroup-users-page .ui-jqgrid-bdiv {
            max-height: none !important;
        }

        .usergroup-users-page .ui-jqgrid .ui-jqgrid-htable th,
        .usergroup-users-page .ui-jqgrid .ui-jqgrid-btable td {
            white-space: normal;
        }

        .usergroup-users-page .ui-jqgrid .ui-pg-table {
            width: auto !important;
        }

        .usergroup-users-page .ui-jqgrid .ui-jqgrid-pager {
            overflow-x: auto;
        }

        .usergroup-users-page .ui-jqgrid .ui-jqgrid-btable td[aria-describedby="usergroups_actions"] a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.2rem;
        }

        .usergroup-users-page .alert {
            margin-bottom: 1rem;
        }
    </style>

    <script>
        $(function() {
            <?php
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

            function resizeUsergroupsGrid() {
                var $grid = $("#usergroups");

                if (!$grid.length || !$grid[0].grid) {
                    return;
                }

                var $container = $(".usergroup-users-page");

                if (!$container.length) {
                    return;
                }

                var newWidth = Math.floor($container.innerWidth());

                if (newWidth > 0) {
                    $grid.jqGrid("setGridWidth", newWidth, true);
                }
            }

            $("#usergroups").jqGrid(
                "navGrid",
                "#usergroups_pager",
                { edit: false, add: false, del: <?php echo $allowEdit ? 'true' : 'false'; ?>, search: false},
                { height:280, reloadAfterSubmit:false },
                { height:280, reloadAfterSubmit:false },
                { reloadAfterSubmit:false, url: "<?php echo $deleteUrl; ?>" },
                { width:500 }
            )
            <?php if ($calendarPlugin) { ?>
                .navButtonAdd('#usergroups_pager',{
                    caption:"<?php echo addslashes($calendarPlugin->get_lang('Update calendar')); ?>",
                    buttonicon:"ui-icon ui-icon-plus",
                    onClickButton: function(a) {
                        var userIdList = $("#usergroups").jqGrid('getGridParam', 'selarrrow');
                        if (userIdList.length) {
                            $(".modal-body #add_multiple_calendar_user_list").val(userIdList);
                            $('#myModal').modal();
                        } else {
                            alert("<?php echo addslashes(get_lang('Select learners')); ?>");
                        }
                    },
                    position:"last"
                })
            <?php } ?>
            ;

            resizeUsergroupsGrid();

            $(window).on("resize.usergroupsGrid", function() {
                resizeUsergroupsGrid();
            });

            setTimeout(function() {
                resizeUsergroupsGrid();
            }, 0);
        });
    </script>

<?php if ($calendarPlugin) { ?>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">
                        <?php echo $calendarPlugin->get_lang('Add multiple users to calendar'); ?>
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

echo '<div class="usergroup-users-page">';

if ($canViewFromCourse && !$allowEdit) {
    echo Display::return_message(
        get_lang('You can view the members of this class from the course, but you cannot edit the class.'),
        'info'
    );
}

$usergroup->displayToolBarUserGroupUsers();

echo '</div>';

Display::display_footer();
