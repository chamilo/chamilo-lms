<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of language-related functions for cases where th user isn't
 * logged in yet.
 *
 * @package chamilo.custompages
 */
// Get helper functions
require_once __DIR__.'/language.inc.php';

// Define the languages you want to make available for auto-detection here
$available_langs = ['en', 'fr', 'es', 'gl', 'eu'];
// Define the translation of these language codes to Chamilo languages
$chamilo_langs = [
    null => 'english',
    'en' => 'english',
    'fr' => 'french',
    'nl' => 'dutch',
    'de' => 'german',
    'es' => 'spanish',
    'gl' => 'galician',
    'eu' => 'basque',
];
$lang_match = $chamilo_langs[get_preferred_language($available_langs)];
// recover previous value ...
if (isset($_SESSION['user_language_choice'])) {
    $lang_match = $_SESSION['user_language_choice'];
}

// Chamilo parameter, on logout
if (isset($_REQUEST['language']) && !empty($_REQUEST['language']) && in_array($_REQUEST['language'], $chamilo_langs)) {
    $lang_match = $_REQUEST['language'];
}
// Incoming link parameter
if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang']) && in_array($_REQUEST['lang'], $available_langs)) {
    $lang_match = $chamilo_langs[$_REQUEST['lang']];
}

$detect = api_get_setting('auto_detect_language_custom_pages');
if ($detect === 'true') {
    // Auto detect
    $_user['language'] = $lang_match;
    $_SESSION['user_language_choice'] = $lang_match;
} else {
    // Chamilo default platform.
    $defaultLanguage = api_get_interface_language();
    $_user['language'] = $defaultLanguage;
    $_SESSION['user_language_choice'] = $defaultLanguage;
}
