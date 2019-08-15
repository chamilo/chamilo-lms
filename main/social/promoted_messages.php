<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

if (api_get_setting('allow_social_tool') !== 'true') {
    api_not_allowed(true);
}

$logInfo = [
    'tool' => 'Messages',
    'action' => 'promoted_messages_list',
];
Event::registerLog($logInfo);

$this_section = SECTION_SOCIAL;
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'social/home.php',
    'name' => get_lang('SocialNetwork'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'social/promoted_messages.php',
    'name' => get_lang('PromotedMessages'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('List')];
$menu = SocialManager::show_social_menu('messages');

// Right content
$social_right_content = '';
$keyword = '';
$actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'social/new_promoted_message.php">'.
    Display::return_icon('new-message.png', get_lang('ComposeMessage'), [], 32).'</a>';

$form = MessageManager::getSearchForm(api_get_self());
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $keyword = $values['keyword'];
}
$actionsRight = $form->returnForm();
$social_right_content .= Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);
$social_right_content .= MessageManager::getPromotedMessagesGrid($keyword);

$tpl = new Template(null);
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');

$tpl->assign('social_menu_block', $menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/inbox.tpl');
$tpl->display($social_layout);
