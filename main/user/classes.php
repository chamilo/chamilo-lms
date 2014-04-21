<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.classes
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Init
 */

$language_file = array('userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'usergroup.lib.php';

api_block_anonymous_users();

$this_section = SECTION_COURSES;

$interbreadcrumb[]= array ('url' =>'classes.php','name' => get_lang('Classes'));
if (isset($_GET['id'])) {
    $interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Groups'));
}

if (api_get_setting('show_groups_to_users') == 'false') {
    
}



Display :: display_header($tool_name, 'Classes');

$usergroup = new Usergroup();
$usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
if (!empty($usergroup_list)) {
    echo Display::tag('h1',get_lang('MyClasses'));
    foreach($usergroup_list as $group_id) {
        if (isset($_GET['id']) && $_GET['id'] != $group_id) continue;
    	$data = $usergroup->get($group_id);
        echo Display::tag('h2',$data['name']);
        echo Display::div($data['description']);
    }
} else {
    if (api_is_platform_admin()) {
        Display::display_normal_message(Display::url(get_lang('AddClasses') ,api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add'), false);
    }
}

Display :: display_footer();
