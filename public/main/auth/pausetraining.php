<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users(true);

$allow = api_get_plugin_setting('pausetraining', 'tool_enable') === 'true';
$allowPauseFormation = api_get_plugin_setting('pausetraining', 'allow_users_to_edit_pause_formation') === 'true';

if (false === $allow || false === $allowPauseFormation) {
    api_not_allowed(true);
}

$userId = api_get_user_id();

$userInfo = api_get_user_info($userId);

$justification = '';
$plugin = PauseTraining::create();

$form = new FormValidator('pausetraining');
$form->addHeader($plugin->get_lang('PauseTraining'));

$extraField = new ExtraField('user');

$return = $extraField->addElements(
    $form,
    $userId,
    [],
    false,
    false,
    ['pause_formation', 'start_pause_date', 'end_pause_date'],
    [],
    [],
    false,
    true
);

$form->addRule(
    ['extra_start_pause_date', 'extra_end_pause_date'],
    get_lang('StartDateShouldBeBeforeEndDate'),
    'date_compare',
    'lte'
);

$form->addButtonSend(get_lang('Update'));
if ($form->validate()) {
    $values = $form->getSubmitValues(1);
    $values['item_id'] = $userId;

    if (!isset($values['extra_pause_formation'])) {
        $values['extra_pause_formation'] = 0;
    }
    $extraField = new ExtraFieldValue('user');
    $extraField->saveFieldValues($values, true, false, [], [], true);

    Display::addFlash(Display::return_message(get_lang('Update')));
    header('Location: '.api_get_self());
    exit;
}

$tabs = SocialManager::getHomeProfileTabs('pausetraining');
$content = $tabs.$form->returnForm();

$tpl = new Template(get_lang('ModifyProfile'));

SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'home');
$menu = SocialManager::show_social_menu(
    'home',
    null,
    api_get_user_id(),
    false,
    false
);

$tpl->assign('social_menu_block', $menu);
$tpl->assign('social_right_content', $content);
$social_layout = $tpl->get_template('social/edit_profile.tpl');

$tpl->display($social_layout);
