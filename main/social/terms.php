<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

if (api_get_setting('allow_terms_conditions') !== 'true') {
    api_not_allowed(true);
}

api_block_anonymous_users();

$language = api_get_interface_language();
$language = api_get_language_id($language);
$term = LegalManager::get_last_condition($language);

if (!$term) {
    // look for the default language
    $language = api_get_setting('platformLanguage');
    $language = api_get_language_id($language);
    $term = LegalManager::get_last_condition($language);
}

$termExtraFields = new ExtraFieldValue('terms_and_condition');
$values = $termExtraFields->getAllValuesByItem($term['id']);
foreach ($values as $value) {
    if (!empty($value['value'])) {
        $term['content'] .= '<h3>'.get_lang($value['display_text']).'</h3><br />'.$value['value'].'<br />';
    }
}

$term['date_text'] = get_lang('PublicationDate').': '.
    api_get_local_time(
        $term['date'],
        null,
        null,
        false,
        true,
        true
    );

$socialMenuBlock = '';
$allowSocial = api_get_setting('allow_social_tool') === 'true';

if ($allowSocial) {
    // Block Social Menu
    $socialMenuBlock = SocialManager::show_social_menu('personal-data');
}

$tpl = new Template(null);

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'social/personal_data.php'
);

$tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if (api_get_setting('allow_social_tool') === 'true') {
    $tpl->assign('social_menu_block', $socialMenuBlock);
} else {
    $tpl->assign('social_menu_block', '');
}
$tpl->assign('term', $term);

$socialLayout = $tpl->get_template('social/terms.tpl');
$tpl->display($socialLayout);
