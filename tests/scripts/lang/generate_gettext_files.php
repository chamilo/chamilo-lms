<?php

/* For licensing terms, see /license.txt */

/**
 * Generate Gettext-format language files from the existing language files.
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */

exit(); //remove before execution
//require_once __DIR__.'/../../../public/main/inc/global.inc.php';
ini_set('memory_limit', '600M');
$partial = false; //if set to true, do not add empty strings to .po files
$destinationDir = '/tmp/gettext'; //where to put the generated files

/**
 * Get list of languages.
 */
$langPath = __DIR__.'/../../../public/main/lang/';
$languagesListFull = scandir($langPath);
$languagesList = [];
foreach ($languagesListFull as $language) {
    if (substr($language, 0, 1) === '.') {
        continue;
    } elseif ($language === 'index.html') {
        continue;
    } else {
        $languagesList[] = $language;
    }
}
require __DIR__.'/../../../public/main/inc/lib/sub_language.class.php';
/**
 * Get English language terms (the main source of terms)
 */
$path = $langPath.'english';
$terms = $originalTerms = [];
$file = $path.'/trad4all.inc.php';
if (is_file($file)) {
    $originalTerms = SubLanguageManager::get_all_language_variable_in_file($file, true);
}
foreach ($originalTerms as $index => $translation) {
    if (!isset($terms[$index])) {
        $terms[$index] = trim(rtrim($translation, ';'), '"');
    }
}
// get only the array keys (the language variables defined in language files)
//$definedTerms = array_flip(array_keys($terms));
echo count($terms)." terms were found in language files".PHP_EOL;

// make sure we have an ISO 639-1 (or-2 if no -1) to give the gettext file
$langToIso639v1 = [
    'arabic'  => 'ar',
    'asturian' => 'ast_ES',
    'basque'  => 'eu_ES',
    'bengali' => 'bn_BD',
    'bosnian' => 'bs_BA',
    'brazilian' => 'pt_BR',
    'bulgarian' => 'bg_BG',
    'catalan' => 'ca',
    'croatian' => 'hr_HR',
    'czech'   => 'cs_CZ',
    'danish'  => 'da',
    'dari'    => 'fa_AF',
    'dutch'   => 'nl',
    'english' => 'en_US',
    'estonian' => 'et',
    'esperanto' => 'eo',
    'faroese' => 'fo_FO',
    'finnish' => 'fi_FI',
    'french'  => 'fr_FR',
    'friulian' => 'fur',
    'galician' => 'gl',
    'georgian' => 'ka_GE',
    'german'  => 'de',
    'greek'   => 'el',
    'hebrew'  => 'he_IL',
    'hindi'   => 'hi',
    'hungarian' => 'hu_HU',
    'indonesian' => 'id_ID',
    'italian' => 'it',
    'japanese' => 'ja',
    'korean'  => 'ko_KR',
    'latvian' => 'lv_LV',
    'lithuanian' => 'lt_LT',
    'macedonian' => 'mk_MK',
    'malay' => 'ms_MY',
    'norwegian' => 'nn_NO',
    'occitan' => 'oc',
    'pashto' => 'ps',
    'persian' => 'fa_IR',
    'polish' => 'pl_PL',
    'portuguese' => 'pt_PT',
    'quechua_cusco' => 'quz_PE',
    'romanian' => 'ro_RO',
    'russian' => 'ru_RU',
    'serbian' => 'sr_RS',
    'simpl_chinese' => 'zh_CN',
    'slovak' => 'sk_SK',
    'slovenian' => 'sl_SI',
    'somali' => 'so_SO',
    'spanish' => 'es',
    'spanish_latin' => 'es_MX',
    'swahili' => 'sw_KE',
    'swedish' => 'sv_SE',
    'tagalog' => 'tl_PH',
    'thai' => 'th',
    'tibetan' => 'bo_CN',
    'trad_chinese' => 'zh_TW',
    'turkish' => 'tr',
    'ukrainian' => 'uk_UA',
    'vietnamese' => 'vi_VN',
    'xhosa' => 'xh_ZA',
    'yoruba' => 'yo_NG',
];

$langToPOFilename = [
    'arabic'  => 'ar',
    'asturian' => 'ast_ES',
    'basque'  => 'eu_ES',
    'bengali' => 'bn',
    'bosnian' => 'bs',
    'brazilian' => 'pt_BR',
    'bulgarian' => 'bg',
    'catalan' => 'ca_ES',
    'croatian' => 'hr',
    'czech'   => 'cs',
    'danish'  => 'da',
    'dari'    => 'fa_AF',
    'dutch'   => 'nl',
    'english' => 'en',
    'estonian' => 'et',
    'esperanto' => 'eo',
    'faroese' => 'fo',
    'finnish' => 'fi',
    'french'  => 'fr',
    'friulian' => 'fur_IT',
    'galician' => 'gl_ES',
    'georgian' => 'ka',
    'german'  => 'de',
    'greek'   => 'el',
    'hebrew'  => 'he',
    'hindi'   => 'hi',
    'hungarian' => 'hu',
    'indonesian' => 'id',
    'italian' => 'it',
    'japanese' => 'ja',
    'korean'  => 'ko',
    'latvian' => 'lv',
    'lithuanian' => 'lt',
    'macedonian' => 'mk',
    'malay' => 'ms',
    'norwegian' => 'nn',
    'occitan' => 'oc_FR',
    'pashto' => 'ps',
    'persian' => 'fa',
    'polish' => 'pl',
    'portuguese' => 'pt',
    'quechua_cusco' => 'qu',
    'romanian' => 'ro',
    'russian' => 'ru',
    'serbian' => 'sr',
    'simpl_chinese' => 'zh_CN',
    'slovak' => 'sk',
    'slovenian' => 'sl',
    'somali' => 'so',
    'spanish' => 'es',
    'spanish_latin' => 'es_MX',
    'swahili' => 'sw',
    'swedish' => 'sv',
    'tagalog' => 'tl',
    'thai' => 'th',
    'tibetan' => 'bo',
    'trad_chinese' => 'zh_TW',
    'turkish' => 'tr',
    'ukrainian' => 'uk',
    'vietnamese' => 'vi',
    'xhosa' => 'xh',
    'yoruba' => 'yo',
];

/**
 * Generate .pot and .po files
 * See https://webtranslateit.com/en/docs/file_formats/gettext_po/
 */
$baseFilename = 'main';
if (!is_dir($destinationDir)) {
    mkdir($destinationDir);
}
if (!is_dir($destinationDir.'/'.$baseFilename)) {
    mkdir($destinationDir.'/'.$baseFilename);
}

$destinationDir .= '/';
$destinationFilePot = $destinationDir.'/'.$baseFilename.'/'.$baseFilename.'.pot';

foreach ($languagesList as $language) {
    $termsInLanguage = $originalTermsInLanguage = [];
    $file = $langPath.$language.'/trad4all.inc.php';
    $languageCode = $langToIso639v1[$language];
    $languageFilename = $langToPOFilename[$language];
    $destinationFile = $destinationDir.'/'.$baseFilename.'/messages.'.$languageFilename.'.po';
    $header = 'msgid ""'."\n".'msgstr ""'."\n".
        '"Project-Id-Version: chamilo\n"'."\n".
        '"Language: '.$languageCode.'\n"'."\n".
        '"Content-Type: text/plain; charset=UTF-8\n"'."\n".
        '"Content-Transfer-Encoding: 8bit\n"'."\n\n";
    file_put_contents($destinationFile, $header);

    $originalTermsInLanguage = SubLanguageManager::get_all_language_variable_in_file(
        $file,
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
        } else {
            if ($partial) {
                //do not include terms with empty translation
                continue;
            }
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
        // Now build the line
        $bigString .= 'msgid "'.$englishTranslation.'"'."\n".'msgstr "'.$translatedTerm.'"'."\n\n";
        if ($language === 'english') {
            //$bigStringPot .= 'msgid "'.$term.'"'."\n".'msgstr ""'."\n\n";
        }
    }
    file_put_contents($destinationFile, $bigString, FILE_APPEND);
    if ($language === 'english') {
        file_put_contents($destinationFilePot, $header.$bigString, FILE_APPEND);
    }
}

echo "Done generating gettext files in $destinationDir!\n";
