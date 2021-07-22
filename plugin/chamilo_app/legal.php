<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script displays a form for registering new users.
 *
 * @package    chamilo.auth
 */

//quick hack to adapt the registration form result to the selected registration language
if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}
require_once __DIR__.'/../../main/inc/global.inc.php';

$language = api_get_interface_language();
$language = api_get_language_id($language);
$term_preview = LegalManager::get_last_condition($language);
if (!$term_preview) {
    //look for the default language
    $language = api_get_setting('platformLanguage');
    $language = api_get_language_id($language);
    $term_preview = LegalManager::get_last_condition($language);
}
$tool_name = get_lang('TermsAndConditions');
Display::display_header($tool_name);

if (!empty($term_preview['content'])) {
    echo $term_preview['content'];
} else {
    echo get_lang('ComingSoon');
}
Display::display_footer();
exit;