<?php
/**
 * Action script for example date plugin.
 *
 * @package chamilo.plugin.date
 */
/**
 * Initialization.
 */
$convert_lang_to_code = [
  "english" => "en_US",
  "french" => "fr_BE",
  "dutch" => "nl_NL",
  "german" => "de_DE",
  "japanese" => "ja_JP",
  "danish" => "da_DK",
];
if (!empty($_SESSION['user_language_choice']) && !empty($convert_lang_to_code[$_SESSION['user_language_choice']])) {
    $code = $convert_lang_to_code[$_SESSION['user_language_choice']];
    $locale = setlocale(LC_TIME, $code);
}
$date = strftime('%c');
