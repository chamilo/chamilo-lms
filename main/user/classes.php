<?php
/* For licensing terms, see /license.txt */
/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_COURSES;

$interbreadcrumb[] = ['url' => 'classes.php', 'name' => get_lang('Classes')];
if (isset($_GET['id'])) {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Groups')];
}

$content = '';

$usergroup = new UserGroup();
$usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
if (!empty($usergroup_list)) {
    $content .= Display::tag('h1', get_lang('MyClasses'));
    foreach ($usergroup_list as $group_id) {
        if (isset($_GET['id']) && $_GET['id'] != $group_id) {
            continue;
        }
        $data = $usergroup->get($group_id);
        $content .= Display::tag('h2', $data['name']);
        $content .= Display::div($data['description']);
    }
} else {
    if (api_is_platform_admin()) {
        Display::addFlash(
            Display::return_message(
                Display::url(
                    get_lang('AddClasses'),
                    api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add'
                ),
                'normal',
                false
            )
        );
    }
}

Display::display_header('', 'Classes');

echo $content;

Display::display_footer();
