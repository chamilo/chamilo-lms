<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$usergroup = new UserGroup();
$usergroup->protectScript();

// Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$userGroupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('Users'),
    get_lang('Courses'),
    get_lang('Sessions'),
    get_lang('Type'),
    get_lang('Actions'),
];

//Column config
$column_model = [
    ['name' => 'name', 'index' => 'name', 'width' => '35', 'align' => 'left'],
    ['name' => 'users', 'index' => 'users', 'width' => '15', 'align' => 'left', 'search' => 'false'],
    ['name' => 'courses', 'index' => 'courses', 'width' => '15', 'align' => 'left', 'search' => 'false'],
    ['name' => 'sessions', 'index' => 'sessions', 'width' => '15', 'align' => 'left', 'search' => 'false'],
    ['name' => 'group_type', 'index' => 'group_type', 'width' => '15', 'align' => 'center', 'search' => 'false'],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '20',
        'align' => 'center',
        'sortable' => 'false',
        'formatter' => 'action_formatter',
        'search' => 'false',
    ],
];

// Autowidth
$extra_params['autowidth'] = 'true';
// Height auto
$extra_params['height'] = 'auto';
$extra_params['sortname'] = 'name';
$extra_params['sortorder'] = 'desc';
// With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
    return \''
    .' <a href="add_users_to_usergroup.php?id=\'+options.rowId+\'">'.Display::return_icon('user_to_class.png', get_lang('SubscribeUsersToClass'), null, ICON_SIZE_MEDIUM).'</a>'
    .' <a href="add_courses_to_usergroup.php?id=\'+options.rowId+\'">'.Display::return_icon('course_to_class.png', get_lang('SubscribeClassToCourses'), null, ICON_SIZE_MEDIUM).'</a>'
    .' <a href="add_sessions_to_usergroup.php?id=\'+options.rowId+\'">'.Display::return_icon('sessions_to_class.png', get_lang('SubscribeClassToSessions'), null, ICON_SIZE_MEDIUM).'</a>'
    .' <a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), null, ICON_SIZE_SMALL).'</a>'
    .' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png', get_lang('Delete'), null, ICON_SIZE_SMALL).'</a>\';
}';

$usergroup->showGroupTypeSetting = true;
$content = '';

// Action handling: Adding a note
switch ($action) {
    case 'add':
        $interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];

        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        $form = new FormValidator(
            'usergroup',
            'post',
            api_get_self().'?action='.$action
        );
        $usergroup->setForm($form, 'add');

        // Setting the defaults
        $form->setDefaults(['visibility' => 2]);

        // The validation or display
        if ($form->validate()) {
            $values = $form->exportValues();
            $res = $usergroup->save($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('ItemAdded'), 'confirmation'));
            } else {
                Display::addFlash(Display::return_message(
                    Security::remove_XSS($values['name']).': '.
                    get_lang('AlreadyExists'),
                    'warning'
                ));
            }
            header('Location: '.api_get_self());
            exit;
        } else {
            $content .= '<div class="actions">';
            $content .= '<a href="'.api_get_self().'">'.
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '</div>';
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $content .= $form->returnForm();
        }
        break;
    case 'edit':
        $interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];

        $defaults = $usergroup->get($userGroupId);
        $usergroup->protectScript($defaults);

        $form = new FormValidator(
            'usergroup',
            'post',
            api_get_self().'?action='.$action.'&id='.$userGroupId
        );

        $usergroup->setForm($form, 'edit', $defaults);

        // Setting the form elements
        $form->addElement('hidden', 'id', $userGroupId);

        // Setting the defaults
        $form->setDefaults($defaults);

        // The validation or display.
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $res = $usergroup->update($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation'));
            } else {
                Display::addFlash(Display::return_message(
                    Security::remove_XSS($values['name']).': '.
                    get_lang('AlreadyExists'),
                    'warning'
                ));
            }
            header('Location: '.api_get_self());
            exit;
        } else {
            $content .= '<div class="actions">';
            $content .= '<a href="'.api_get_self().'">'.Display::return_icon(
                'back.png',
                get_lang('Back'),
                '',
                ICON_SIZE_MEDIUM
            ).'</a>';
            $content .= '</div>';
            $content .= $form->returnForm();
        }
        break;
    case 'delete':
        $defaults = $usergroup->get($userGroupId);
        $usergroup->protectScript($defaults);
        $res = $usergroup->delete($userGroupId);
        if ($res) {
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
        }
        header('Location: '.api_get_self());
        exit;
        break;
    default:
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Classes')];
        $content = $usergroup->returnGrid();
        break;
}

// The header.
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
                $extra_params,
                [],
                $action_links,
                true
            );
            ?>

            $('#usergroups').jqGrid(
                'filterToolbar',
                {stringResult: true, searchOnEnter: false, defaultSearch : "cn"}
            );
        });
    </script>
<?php

echo $content;

Display::display_footer();
