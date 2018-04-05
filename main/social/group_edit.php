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
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;

$group_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
$tool_name = get_lang('GroupEdit');
$usergroup = new UserGroup();
$group_data = $usergroup->get($group_id);

if (empty($group_data)) {
    header('Location: group_view.php?id='.$group_id);
    exit;
}

$interbreadcrumb[] = ['url' => 'groups.php', 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_view.php?id='.$group_id, 'name' => $group_data['name']];

// only group admins can edit the group
if (!$usergroup->is_group_admin($group_id)) {
    api_not_allowed();
}

// Create the form
$form = new FormValidator('group_edit', 'post', '', '');
$form->addElement('hidden', 'id', $group_id);
$usergroup->setGroupType($usergroup::SOCIAL_CLASS);
$usergroup->setForm($form, 'edit', $group_data);

// Set default values
$form->setDefaults($group_data);

// Validate form
if ($form->validate()) {
    $group = $form->exportValues();
    $group['id'] = $group_id;
    $group['group_type'] = $usergroup::SOCIAL_CLASS;
    $usergroup->update($group);
    Display::addFlash(Display::return_message(get_lang('GroupUpdated')));
    header('Location: group_view.php?id='.$group_id);
    exit();
}

$social_left_content = SocialManager::show_social_menu('group_edit', $group_id);
$social_right_content = $form->returnForm();

$tpl = new Template(get_lang('Edit'));

SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'groups', $group_id);

$tpl->setHelp('Groups');
$tpl->assign('social_menu_block', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);

$social_layout = $tpl->get_template('social/add_groups.tpl');
$tpl->display($social_layout);
