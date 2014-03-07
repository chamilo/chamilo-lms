<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file= 'userInfo';
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

if (api_get_setting('allow_students_to_create_groups_in_social') == 'false' && !api_is_allowed_to_edit()) {
	api_not_allowed();
}

$table_message = Database::get_main_table(TABLE_MESSAGE);
$usergroup = new UserGroup();
$form = new FormValidator('add_group');
$usergroup->setGroupType($usergroup::SOCIAL_CLASS);
$usergroup->setForm($form, 'add', array());

if ($form->validate()) {
	$values = $form->exportValues();
    $groupId = $usergroup->save($values);
	header('Location: groups.php?id='.$groupId.'&action=show_message&message='.urlencode(get_lang('GroupAdded')));
	exit();
}

$nameTools = get_lang('AddGroup');
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[]= array ('url' =>'#','name' => $nameTools);

$social_left_content = SocialManager::show_social_menu('group_add');

$social_right_content = '<div class="span9">';
$social_right_content .= $form->return_form();
$social_right_content .= '</div>';

$tpl = $app['template'];
$tpl->setHelp('Groups');
$tpl->assign('content', $social_right_content);
