<?php

/* For licensing terms, see /license.txt */

/**
 * This script generates a CSV file of all settings in the order they appear
 * in the platform settings section, in the given language.
 * This is meant to speed up the redaction of the admin guide.
 * Note: to obtain the default values for all settings, you need to run
 * this script on a freshly installed Chamilo setup with all options set
 * by default in the installation process.
 * @package chamilo.tests.scripts
 */
/**
 * Init
 */
// comment exit statement before executing
exit;

/* Usage doc */
if ($argc <= 1) {
    echo 'Usage: php settings2csv.php [language]'.PHP_EOL;
    echo '  Where the language name is supported by Chamilo. e.g. french, spanish, ...'.PHP_EOL;
    echo '  (defaults to "english").'.PHP_EOL;
    echo '  The resulting filepath will appear on the output line'.PHP_EOL.PHP_EOL;
}

$language = 'english';
if (!empty($argv[1])) {
    $language = $argv[1];
}
$_GET['language'] = $language;
@require __DIR__ . '/../../main/inc/global.inc.php';

// Categories, in order of appearance in the Chamilo settings page
// Check the end of main/admin/settings.php for the initial list
$categories = [
    'Platform',
    'Course',
    'Session',
    'Languages',
    'User',
    'Tools',
    'Editor',
    'Security',
    'Tuning',
    'Gradebook',
    'Timezones',
    'Tracking',
    'Search',
    'Stylesheets',
    'Templates',
    'Plugins',
    'LDAP',
    'CAS',
    'Shibboleth',
    'Facebook',
    'Crons',
    'WebServices',
];

$fileName = 'settings'; // will be appended a ".csv" extension
$fileContent = [];
$fileContent[] = [
    'Variable',
    'Subkey',
    'Comment',
    'Current value'
];

foreach ($categories as $category) {
    $fileContent[] = [
        '***** '.get_lang('Category', null, $language).': '.$category.' ****',
        '',
        '',
        ''
    ];
    $settings = api_get_settings($category, 'group');
    foreach ($settings as $setting) {
        $fileContent[] = [
            get_lang($setting['title'], null, $language),
            $setting['subkey'],
            get_lang($setting['comment'], null, $language),
            $setting['selected_value']
        ];
    }
}

$filePath = Export::arrayToCsv($fileContent, $fileName, true);

echo "File generated and stored in $filePath".PHP_EOL;
