<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.user
*/

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script(true);

if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'false') {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

$tool_name = get_lang("Classes");

$htmlHeadXtra[] = api_get_jqgrid_js();

// Extra entries in breadcrumb
$interbreadcrumb[] = array(
    "url" => "user.php?".api_get_cidreq(),
    "name" => get_lang("ToolUser"),
);

$type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : 'registered';
$groupFilter = isset($_GET['group_filter']) ? intval($_GET['group_filter']) : 0;

$htmlHeadXtra[] = '
<script>
$(document).ready( function() {
    $("#group_filter").change(function() {
        window.location = "class.php?'.api_get_cidreq().'&type='.$type.'" +"&group_filter=" + $(this).val();
    });
});
</script>';

$actionsLeft = '';
$actionsRight = '';
$usergroup = new UserGroup();
if (api_is_allowed_to_edit()) {
    if ($type === 'registered') {
        $actionsLeft .= '<a href="class.php?'.api_get_cidreq().'&type=not_registered">'.
            Display::return_icon('add-class.png', get_lang("AddClassesToACourse"), array(), ICON_SIZE_MEDIUM).'</a>';
    } else {
        $actionsLeft .= '<a href="class.php?'.api_get_cidreq().'&type=registered">'.
            Display::return_icon('back.png', get_lang("Classes"), array(), ICON_SIZE_MEDIUM).'</a>';

        $form = new FormValidator('groups', 'post', api_get_self(), '', '', FormValidator::LAYOUT_INLINE);
        $options = [
            -1 => get_lang('All'),
            1 => get_lang('SocialGroups'),
            0 => get_lang('Classes')
        ];
        $form->addSelect('group_filter', get_lang('Groups'), $options, ['id' => 'group_filter']);
        $form->setDefaults(['group_filter' => $groupFilter]);
        $actionsRight = $form->returnForm();
    }
    $actions = Display::toolbarAction('actions-class', [$actionsLeft, $actionsRight]);
}

if (api_is_allowed_to_edit()) {
    $action = isset($_GET['action']) ? $_GET['action'] : null;
    switch ($action) {
        case 'add_class_to_course':
            $id = $_GET['id'];
            if (!empty($id)) {
                $usergroup->subscribe_courses_to_usergroup(
                    $id,
                    array(api_get_course_int_id()),
                    false
                );
                Display::addFlash(Display::return_message(get_lang('Added')));
            }
            break;
        case 'remove_class_from_course':
            $id = $_GET['id'];
            if (!empty($id)) {
                $usergroup->unsubscribe_courses_from_usergroup(
                    $id,
                    array(api_get_course_int_id())
                );
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
            break;
    }
}

//jqgrid will use this URL to do the selects

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups_teacher&type='.$type.'&group_filter='.$groupFilter;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(
    get_lang('Name'),
    get_lang('Users'),
    get_lang('Status'),
    get_lang('Type'),
    get_lang('Actions'),
);

// Column config
$columnModel = array(
    array('name'=>'name',
        'index' => 'name',
        'width' => '35',
        'align' => 'left',
    ),
    array(
        'name' => 'users',
        'index' => 'users',
        'width' => '15',
        'align' => 'left',
    ),
    array(
        'name' => 'status',
        'index' => 'status',
        'width' => '15',
        'align' => 'left',
    ),
    array(
        'name' => 'group_type',
        'index' => 'group_type',
        'width' => '15',
        'align' => 'center',
    ),
    array(
        'name' => 'actions',
        'index' => 'actions',
        'width' => '10',
        'align' => 'center',
        'sortable' => 'false',
    ),
);
// Autowidth
$extraParams['autowidth'] = 'true';
// height auto
$extraParams['height'] = 'auto';

Display :: display_header($tool_name, "User");

?>
<script>
$(function() {
<?php
    // grid definition see the $usergroup>display() function
    echo Display::grid_js('usergroups',  $url, $columns, $columnModel, $extraParams, array(), '', true);
?>
});
</script>
<?php

echo $actions;
echo UserManager::getUserSubscriptionTab(4);

$usergroup->display_teacher_view();
Display :: display_footer();
