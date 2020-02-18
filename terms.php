<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/main/inc/global.inc.php';

if (api_get_setting('allow_terms_conditions') !== 'true') {
    api_not_allowed(true);
}

if (api_is_anonymous() && api_get_configuration_value('gdpr_terms_public') !== true) {
    api_not_allowed(true);
}

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

$tpl = new Template(null);

$tpl->assign('term', $term);

$socialLayout = $tpl->get_template('user_portal/terms.tpl');
$tpl->display($socialLayout);
