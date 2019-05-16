<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('MySpace');

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

$form = new FormValidator('survey');
$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
    ]
);
$form->addButtonSearch();

$userInfo = [];
if ($form->validate()) {
    $userId = $form->exportValue('user_id');
    $userInfo = api_get_user_info($userId);
}

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();

$form->display();

if (!empty($userInfo)) {
    echo Display::page_subheader($userInfo['complete_name']);
    echo SurveyManager::surveyReport($userInfo);
    echo SurveyManager::surveyReport($userInfo, 1);
}

Display::display_footer();
