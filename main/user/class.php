<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.user
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script(true, false, 'user');

if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'false') {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

$tool_name = get_lang('Classes');

$htmlHeadXtra[] = api_get_jqgrid_js();

// Extra entries in breadcrumb
$interbreadcrumb[] = [
    'url' => 'user.php?'.api_get_cidreq(),
    'name' => get_lang('Users'),
];

$type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : 'registered';
$groupFilter = isset($_GET['group_filter']) ? (int) $_GET['group_filter'] : 0;
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : '';

$htmlHeadXtra[] = '
<script>
$(function() {
    $("#group_filter").change(function() {
        window.location = "class.php?'.api_get_cidreq().'&type='.$type.'" +"&group_filter=" + $(this).val();
    });
});
</script>';

$actionsLeft = '';
$actionsRight = '';
$usergroup = new UserGroup();
$actions = '';

if (api_is_allowed_to_edit()) {
    if ($type === 'registered') {
        $actionsLeft .= '<a href="class.php?'.api_get_cidreq().'&type=not_registered">'.
            Display::return_icon('add-class.png', get_lang('Add classes to a course'), [], ICON_SIZE_MEDIUM).'</a>';
    } else {
        $actionsLeft .= '<a href="class.php?'.api_get_cidreq().'&type=registered">'.
            Display::return_icon('back.png', get_lang('Classes'), [], ICON_SIZE_MEDIUM).'</a>';

        $form = new FormValidator(
            'groups',
            'get',
            api_get_self().'?type='.$type,
            '',
            [],
            FormValidator::LAYOUT_INLINE
        );
        $options = [
            -1 => get_lang('All'),
            1 => get_lang('Social groups'),
            0 => get_lang('Classes'),
        ];
        $form->addSelect(
            'group_filter',
            get_lang('Groups'),
            $options,
            ['id' => 'group_filter', 'disable_js' => 'disable_js']
        );
        $form->addHidden('type', $type);
        $form->addText('keyword', '', false);
        $form->setDefaults(['group_filter' => $groupFilter]);
        $form->addCourseHiddenParams();
        $form->addButtonSearch(get_lang('Search'));

        $actionsRight .= $form->returnForm();
    }

    $actions = Display::toolbarAction('actions-class', [$actionsLeft, $actionsRight]);
    $action = isset($_GET['action']) ? $_GET['action'] : null;
    switch ($action) {
        case 'add_class_to_course':
            $id = $_GET['id'];
            if (!empty($id)) {
                $usergroup->subscribe_courses_to_usergroup(
                    $id,
                    [api_get_course_int_id()],
                    false
                );
                Display::addFlash(Display::return_message(get_lang('Added')));
                header('Location: class.php?'.api_get_cidreq().'&type=registered');
                exit;
            }
            break;
        case 'remove_class_from_course':
            $id = $_GET['id'];
            if (!empty($id)) {
                $usergroup->unsubscribe_courses_from_usergroup(
                    $id,
                    [api_get_course_int_id()]
                );
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
            break;
    }
}

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups_teacher&type='.$type.'&group_filter='.$groupFilter.'&keyword='.$keyword;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('Users'),
    get_lang('Status'),
    get_lang('Type'),
    get_lang('Detail'),
];

// Column config
$columnModel = [
    ['name' => 'name',
        'index' => 'name',
        'width' => '35',
        'align' => 'left',
    ],
    [
        'name' => 'users',
        'index' => 'users',
        'width' => '15',
        'align' => 'left',
    ],
    [
        'name' => 'status',
        'index' => 'status',
        'width' => '15',
        'align' => 'left',
    ],
    [
        'name' => 'group_type',
        'index' => 'group_type',
        'width' => '15',
        'align' => 'center',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '10',
        'align' => 'center',
        'sortable' => 'false',
    ],
];
// Autowidth
$extraParams['autowidth'] = 'true';
// height auto
$extraParams['height'] = 'auto';

Display::display_header($tool_name, 'User');

?>
<script>
$(function() {
<?php
    // grid definition see the $usergroup>display() function
    echo Display::grid_js(
        'usergroups',
        $url,
        $columns,
        $columnModel,
        $extraParams,
        [],
        '',
        true
    );
?>
});
</script>
<?php

echo $actions;
echo UserManager::getUserSubscriptionTab(4);
echo Display::return_message(get_lang('Information: The list of classes below contains the list of classes you have already registered in your course. If this list is empty, use the + green above to add classes.'));
$usergroup->display_teacher_view();
Display::display_footer();
