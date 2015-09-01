<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.classes
 * @author Julio Montoya <gugli100@gmail.com>
 */

$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_COURSES;

$interbreadcrumb[]= array ('url' =>'classes.php','name' => get_lang('Classes'));
if (isset($_GET['id'])) {
    $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Groups'));
}

Display :: display_header($tool_name, 'Classes');

$usergroup = new UserGroup();
$usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
if (!empty($usergroup_list)) {
    echo Display::tag('h1', get_lang('MyClasses'));
    foreach ($usergroup_list as $group_id) {
        if (isset($_GET['id']) && $_GET['id'] != $group_id) {
            continue;
        }
        $data = $usergroup->get($group_id);
        echo Display::tag('h2', $data['name']);
        echo Display::div($data['description']);
    }
} else {
    if (api_is_platform_admin()) {
        Display::display_normal_message(
            Display::url(
                get_lang('AddClasses'),
                api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add'
            ),
            false
        );
    }
}

Display :: display_footer();
