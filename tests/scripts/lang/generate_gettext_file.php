<?php

/* For licensing terms, see /license.txt */

exit;

// Generates a po file from a trad4all.inc.php file.

require_once __DIR__.'/../../../vendor/autoload.php';

ini_set('memory_limit', '600M');

// 1. Source trad4all.inc.php
$langPath = __DIR__.'/../../../trad4all.inc.php';
// 2. Destination po file
$destinationFile = __DIR__.'/../../../trad4all.inc.php.po';
// 3. Iso code
$languageCode = 'fr_FR';

$originalFile = __DIR__.'/../../../public/main/lang/english/trad4all.inc.php';
$terms = SubLanguageManager::get_all_language_variable_in_file(
    $originalFile,
    true
);

foreach ($terms as $index => $translation) {
    $terms[$index] = trim(rtrim($translation, ';'), '"');
}

$header = 'msgid ""'."\n".'msgstr ""'."\n".
    '"Project-Id-Version: chamilo\n"'."\n".
    '"Language: '.$languageCode.'\n"'."\n".
    '"Content-Type: text/plain; charset=UTF-8\n"'."\n".
    '"Content-Transfer-Encoding: 8bit\n"'."\n\n";
file_put_contents($destinationFile, $header);

$originalTermsInLanguage = SubLanguageManager::get_all_language_variable_in_file(
    $langPath,
    true
);
foreach ($originalTermsInLanguage as $id => $content) {    
    if (!isset($termsInLanguage[$id])) {
        $termsInLanguage[$id] = trim(rtrim($content, ';'), '"');
    }
}

$bigString = '';
$bigStringPot = '';
$doneTranslations = [];
foreach ($terms as $term => $englishTranslation) {
    if (isset($doneTranslations[$englishTranslation])) {
        continue;
    }
    $doneTranslations[$englishTranslation] = true;
    $translatedTerm = '';
    if (!empty($termsInLanguage[$term])) {
        $translatedTerm = $termsInLanguage[$term];
    }
    // Here we apply a little correction to avoid unterminated strings
    // when a string ends with a \"
    if (preg_match('/\\\$/', $englishTranslation)) {
        $englishTranslation .= '"';
    }
    $englishTranslation2 = '';
    $search = ['\\{', '\\}', '\\(', '\\)', '\\;'];
    $replace = ['\\\\{', '\\\\}', '\\\\(', '\\\\)', '\\\\;'];
    $englishTranslation = str_replace($search, $replace, $englishTranslation);
    if (preg_match('/\\\$/', $translatedTerm)) {
        $translatedTerm .= '"';
    }
    $translatedTerm = str_replace($search, $replace, $translatedTerm);
    if (empty($translatedTerm)) {
        continue;
    }
    // Now build the line
    $bigString .= 'msgid "'.$englishTranslation.'"'."\n".'msgstr "'.$translatedTerm.'"'."\n\n";
}
file_put_contents($destinationFile, $bigString, FILE_APPEND);

echo "Done generating gettext file in $destinationFile !\n";
