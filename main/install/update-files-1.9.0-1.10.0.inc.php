<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Chamilo files from version 1.9.0 to version 1.10.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 * @package chamilo.install
 */
Log::notice('Entering file');

if (defined('SYSTEM_INSTALLATION')) {

    $conf_dir = api_get_path(CONFIGURATION_PATH);

    // Changes for 1.10.x
    // Delete directories and files that are not necessary anymore
    // pChart (1) lib, etc

    // Delete the "chat" file in all language directories, as variables have been moved to the trad4all file
    $langPath = api_get_path(SYS_CODE_PATH).'lang/';
    // Only erase files from Chamilo languages (not sublanguages defined by the users)
    $officialLanguages = array(
        'arabic',
        'asturian',
        'basque',
        'bengali',
        'bosnian',
        'brazilian',
        'bulgarian',
        'catalan',
        'croatian',
        'czech',
        'danish',
        'dari',
        'dutch',
        'english',
        'esperanto',
        'faroese',
        'finnish',
        'french',
        'friulian',
        'galician',
        'georgian',
        'german',
        'greek',
        'hebrew',
        'hindi',
        'hungarian',
        'indonesian',
        'italian',
        'japanese',
        'korean',
        'latvian',
        'lithuanian',
        'macedonian',
        'malay',
        'norwegian',
        'occitan',
        'pashto',
        'persian',
        'polish',
        'portuguese',
        'quechua_cusco',
        'romanian',
        'russian',
        'serbian',
        'simpl_chinese',
        'slovak',
        'slovenian',
        'somali',
        'spanish',
        'spanish_latin',
        'swahili',
        'swedish',
        'tagalog',
        'thai',
        'tibetan',
        'trad_chinese',
        'turkish',
        'ukrainian',
        'vietnamese',
        'xhosa',
        'yoruba',
    );
    $list = scandir($langPath);
    foreach ($list as $entry) {
        if (is_dir($langPath.$entry) && in_array($entry, $officialLanguages)) {
            unlink($langPath.$entry.'/accessibility.inc.php');
            unlink($langPath.$entry.'/chat.inc.php');
            unlink($langPath.$entry.'/course_description.inc.php');
            unlink($langPath.$entry.'/course_home.inc.php');
            unlink($langPath.$entry.'/external_module.inc.php');
            unlink($langPath.$entry.'/glossary.inc.php');
            unlink($langPath.$entry.'/import.inc.php');
            unlink($langPath.$entry.'/md_mix.inc.php');
            unlink($langPath.$entry.'/messages.inc.php');
            unlink($langPath.$entry.'/myagenda.inc.php');
            unlink($langPath.$entry.'/notebook.inc.php');
            unlink($langPath.$entry.'/pedaSuggest.inc.php');
            unlink($langPath.$entry.'/resourcelinker.inc.php');
            unlink($langPath.$entry.'/scormbuilder.inc.php');
            unlink($langPath.$entry.'/scormdocument.inc.php');
            unlink($langPath.$entry.'/slideshow.inc.php');
        }
    }

} else {
    echo 'You are not allowed here !'. __FILE__;
}
