<?php
/* For licensing terms, see /license.txt */
/**
 * Generate Gettext-format language files from the existing language files
 *
 * @package chamilo.cron.lang
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
/**
 * Includes and declarations.
 */
die(); //remove before execution
require_once __DIR__.'/../../../main/inc/global.inc.php';
ini_set('memory_limit', '128M');
$partial = false;//if set to true, do not add empty strings to .po files
$destinationDir = '/tmp/gettext'; //where to put the generated files
/**
 * Get list of languages
 */
$langPath = api_get_path(SYS_LANG_PATH);
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

/**
 * Get English language terms (the main source of terms)
 */
$path = $langPath.'english';
$terms = [];
$file = $path.'/trad4all.inc.php';
if (is_file($file)) {
    $terms = SubLanguageManager::get_all_language_variable_in_file($file, true);
}
foreach ($terms as $index => $translation) {
    $terms[$index] = trim(rtrim($translation, ';'), '"');
}
// get only the array keys (the language variables defined in language files)
//$definedTerms = array_flip(array_keys($terms));
echo count($terms)." terms were found in language files".PHP_EOL;

// make sure we have an ISO 639-1 (or-2 if no -1) to give the gettext file
$langToIso639v1 = [
    'arabic'  => 'ar_AR',
    'asturian' => 'ast_ES',
    'basque'  => 'eu_ES',
    'bengali' => 'bn_BD',
    'bosnian' => 'bs_BA',
    'brazilian' => 'pt_BR',
    'bulgarian' => 'bg_BG',
    'catalan' => 'ca_ES',
    'croatian' => 'hr_HR',
    'czech'   => 'cs_CZ',
    'danish'  => 'da_DK',
    'dari'    => 'prs_AF',
    'dutch'   => 'nl_NL',
    'english' => 'en_US',
    'esperanto' => 'eo',
    'faroese' => 'fo_FO',
    'finnish' => 'fi_FI',
    'french'  => 'fr_FR',
    'friulian' => 'fur_IT',
    'galician' => 'gl_ES',
    'georgian' => 'ka_GE',
    'german'  => 'de_DE',
    'greek'   => 'el_GR',
    'hebrew'  => 'he_IL',
    'hindi'   => 'hi_IN',
    'hungarian' => 'hu_HU',
    'indonesian' => 'id_ID',
    'italian' => 'it_IT',
    'japanese' => 'ja_JP',
    'korean'  => 'ko_KR',
    'latvian' => 'lv_LV',
    'lithuanian' => 'lt_LT',
    'macedonian' => 'mk_MK',
    'malay' => 'ms_MY',
    'norwegian' => 'nn_NO',
    'occitan' => 'oc_FR',
    'pashto' => 'ps_AF',
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
    'spanish' => 'es_ES',
    'spanish_latin' => 'es_MX',
    'swahili' => 'sw_KE',
    'swedish' => 'sv_SE',
    'tagalog' => 'tl_PH',
    'thai' => 'th_TH',
    'tibetan' => 'bo_CN',
    'trad_chinese' => 'zh_TW',
    'turkish' => 'tr_TR',
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
    'dari'    => 'prs_AF',
    'dutch'   => 'nl',
    'english' => 'en',
    'esperanto' => 'eo',
    'faroese' => 'fo_FO',
    'finnish' => 'fi',
    'french'  => 'fr',
    'friulian' => 'fur_IT',
    'galician' => 'gl_ES',
    'georgian' => 'ka_GE',
    'german'  => 'de',
    'greek'   => 'el',
    'hebrew'  => 'he',
    'hindi'   => 'hi_IN',
    'hungarian' => 'hu',
    'indonesian' => 'id',
    'italian' => 'it',
    'japanese' => 'ja',
    'korean'  => 'ko',
    'latvian' => 'lv',
    'lithuanian' => 'lt',
    'macedonian' => 'mk',
    'malay' => 'ms_MY',
    'norwegian' => 'nn',
    'occitan' => 'oc_FR',
    'pashto' => 'ps_AF',
    'persian' => 'fa_IR',
    'polish' => 'pl',
    'portuguese' => 'pt',
    'quechua_cusco' => 'quz_PE',
    'romanian' => 'ro',
    'russian' => 'ru',
    'serbian' => 'sr_RS',
    'simpl_chinese' => 'zh_CN',
    'slovak' => 'sk',
    'slovenian' => 'sl',
    'somali' => 'so',
    'spanish' => 'es',
    'spanish_latin' => 'es_MX',
    'swahili' => 'sw_KE',
    'swedish' => 'sv',
    'tagalog' => 'tl_PH',
    'thai' => 'th',
    'tibetan' => 'bo_CN',
    'trad_chinese' => 'zh_TW',
    'turkish' => 'tr',
    'ukrainian' => 'uk_UA',
    'vietnamese' => 'vi',
    'xhosa' => 'xh_ZA',
    'yoruba' => 'yo_NG',
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
    $termsInLanguage = [];
    $file = $langPath.$language.'/trad4all.inc.php';
    $languageCode = $langToIso639v1[$language];
    $languageFilename = $langToPOFilename[$language];
    $destinationFile = $destinationDir.'/'.$baseFilename.'/'.$languageFilename.'.po';
    $header = 'msgid ""'."\n".'msgstr ""'."\n".
        '"Project-Id-Version: chamilo-lms\n"'."\n".
        '"Language: '.$languageCode.'\n"'."\n".
        '"Content-Type: text/plain; charset=UTF-8\n"'."\n".
        '"Content-Transfer-Encoding: 8bit\n"'."\n\n";
    file_put_contents($destinationFile, $header);

    $termsInLanguage = SubLanguageManager::get_all_language_variable_in_file(
        $file,
        true
    );
    foreach ($termsInLanguage as $id => $content) {
        $termsInLanguage[$id] = trim(rtrim($content, ';'), '"');
    }

    $bigString = '';
    $bigStringPot = '';
    foreach ($terms as $term => $englishTranslation) {
        $translatedTerm = '';
        if (!empty($termsInLanguage[$term])) {
            $translatedTerm = $termsInLanguage[$term];
        } else {
            if ($partial) {
                //do not include terms with empty translation
                continue;
            }
        }
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
