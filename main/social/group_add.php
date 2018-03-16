<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.social
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !== 'true') {
    api_not_allowed(true);
}

if (api_get_setting('allow_students_to_create_groups_in_social') === 'false' && !api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$table_message = Database::get_main_table(TABLE_MESSAGE);
$usergroup = new UserGroup();
$form = new FormValidator('add_group');
$usergroup->setGroupType($usergroup::SOCIAL_CLASS);
$usergroup->setForm($form, 'add', []);

if ($form->validate()) {
    $values = $form->exportValues();
    $values['group_type'] = UserGroup::SOCIAL_CLASS;
    $values['relation_type'] = GROUP_USER_PERMISSION_ADMIN;

    $groupId = $usergroup->save($values);
    Display::addFlash(Display::return_message(get_lang('GroupAdded')));
    header('Location: group_view.php?id='.$groupId);
    exit();
}

$nameTools = get_lang('AddGroup');
$this_section = SECTION_SOCIAL;

$interbreadcrumb[] = ['url' => 'home.php', 'name' => get_lang('Social')];
$interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => '#', 'name' => $nameTools];

$social_avatar_block = SocialManager::show_social_avatar_block('group_add');
$social_menu_block = SocialManager::show_social_menu('group_add');
$social_right_content = $form->returnForm();

$tpl = new Template(null);
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), null, null);
$tpl->setHelp('Groups');
$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);

$social_layout = $tpl->get_template('social/add_groups.tpl');
$tpl->display($social_layout);
