<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Usergroup;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
if ('true' !== api_get_setting('allow_social_tool')) {
    api_not_allowed(true);
}

if ('false' === api_get_setting('allow_students_to_create_groups_in_social') && !api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$table_message = Database::get_main_table(TABLE_MESSAGE);
$usergroup = new UserGroupModel();
$form = new FormValidator('add_group');
$usergroup->setGroupType(Usergroup::SOCIAL_CLASS);
$usergroup->setForm($form, 'add');

if ($form->validate()) {
    $values = $form->exportValues();
    $values['group_type'] = Usergroup::SOCIAL_CLASS;
    $values['relation_type'] = GROUP_USER_PERMISSION_ADMIN;
    $groupId = $usergroup->save($values);
    if ($groupId) {
        Display::addFlash(Display::return_message(get_lang('Group added')));
        api_location(api_get_path(WEB_CODE_PATH).'social/group_view.php?id='.$groupId);
    }
    api_location(api_get_self());
}

$nameTools = get_lang('Add group');
$this_section = SECTION_SOCIAL;

$interbreadcrumb[] = ['url' => 'home.php', 'name' => get_lang('Social')];
$interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => '#', 'name' => $nameTools];

$social_avatar_block = SocialManager::show_social_avatar_block('group_add');
$social_menu_block = SocialManager::show_social_menu('group_add');

$tpl = new Template(null);
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), null, null);
$tpl->setHelp('Groups');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $form->returnForm());

$social_layout = $tpl->get_template('social/add_groups.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
