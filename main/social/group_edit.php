<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
// Language files that should be included
$language_file = array('userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;

$group_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
$tool_name = get_lang('GroupEdit');

$interbreadcrumb[] = array('url' => 'groups.php', 'name' => get_lang('Groups'));

$usergroup = new UserGroup();

$group_data = $usergroup->get($group_id);
if (empty($group_data)) {
    header('Location: groups.php?id='.$group_id);
    exit;
}

//only group admins can edit the group
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
    $group['type'] = $usergroup::SOCIAL_CLASS;
    $usergroup->update($group);
    $tok = Security::get_token();
    header(
        'Location: groups.php?id='.$group_id.'&action=show_message&message='.urlencode(
            get_lang('GroupUpdated')
        ).'&sec_token='.$tok
    );
    exit();
}

$social_left_content = SocialManager::show_social_menu('group_edit', $group_id);
$social_right_content = '<div class="span9">';
$social_right_content .= $form->return_form();
$social_right_content .= '</div>';

$app['title'] = $tool_name;
$tpl = $app['template'];
$tpl->setHelp('Groups');
$tpl->assign('content', $social_right_content);
