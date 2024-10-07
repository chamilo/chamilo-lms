<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$usergroup = new UserGroupModel();
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
    get_lang('Title'),
    get_lang('Users'),
    get_lang('Courses'),
    get_lang('Course sessions'),
    get_lang('Type'),
    get_lang('Detail'),
];

//Column config
$column_model = [
    ['name' => 'title', 'index' => 'title', 'align' => 'left', 'width' => '400'],
    ['name' => 'users', 'index' => 'users', 'align' => 'left', 'search' => 'false'],
    ['name' => 'courses', 'index' => 'courses', 'align' => 'left', 'search' => 'false'],
    ['name' => 'sessions', 'index' => 'sessions', 'align' => 'left', 'search' => 'false'],
    ['name' => 'group_type', 'index' => 'group_type', 'align' => 'center', 'search' => 'false'],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '250',
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
$extra_params['sortname'] = 'title';
$extra_params['sortorder'] = 'desc';
// With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
    return \''
    .' <a href="add_users_to_usergroup.php?id=\'+options.rowId+\'">'.Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Subscribe users to class')).'</a>'
    .' <a href="add_courses_to_usergroup.php?id=\'+options.rowId+\'">'.Display::getMdiIcon(ObjectIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Subscribe class to courses')).'</a>'
    .' <a href="add_sessions_to_usergroup.php?id=\'+options.rowId+\'">'.Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Subscribe class to sessions')).'</a>'
    .' <a href="?action=edit&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>'
    .' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."\'".')) return false;"  href="?action=delete&id=\'+options.rowId+\'">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>\';
}';

$usergroup->showGroupTypeSetting = true;
$content = '';

// Action handling: Adding a note
switch ($action) {
    case 'add':
        $interbreadcrumb[] = ['url' => 'usergroups.php', 'name' => get_lang('Classes')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];

        if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
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
                Display::addFlash(Display::return_message(get_lang('Item added'), 'confirmation'));
            } else {
                Display::addFlash(Display::return_message(
                    Security::remove_XSS($values['title']).': '.
                    get_lang('Already exists'),
                    'warning'
                ));
            }
            header('Location: '.api_get_self());
            exit;
        } else {
            $actions = '<a href="'.api_get_self().'">'.
                Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')).'</a>';
            $content .= Display::toolbarAction('toolbar', [$actions]);
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
        if (empty($defaults)) {
            api_not_allowed(true);
        }

        $usergroup->protectScript($defaults);

        $form = new FormValidator(
            'usergroup',
            'post',
            api_get_self().'?action='.$action.'&id='.$userGroupId
        );

        $repo = Container::getUsergroupRepository();
        $usergroup->setForm($form, 'edit', $repo->find($userGroupId));

        // Setting the form elements
        $form->addElement('hidden', 'id', $userGroupId);

        // Setting the defaults
        $form->setDefaults($defaults);

        // The validation or display.
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $res = $usergroup->update($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('Update successful'), 'confirmation'));
            } else {
                Display::addFlash(Display::return_message(
                    Security::remove_XSS($values['title']).': '.
                    get_lang('Already exists'),
                    'warning'
                ));
            }
            header('Location: '.api_get_self());
            exit;
        } else {
            $actions = '<a href="'.api_get_self().'">'.Display::getMdiIcon(
                ActionIcon::BACK,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Back')
            ).'</a>';
            $content .= Display::toolbarAction('toolbar', [$actions]);
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
