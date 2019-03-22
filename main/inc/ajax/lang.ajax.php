<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_course_script(true);

$action = $_REQUEST['a'];

switch ($action) {
    case 'translate_html':
        $translate = api_get_configuration_value('translate_html');

        if (!$translate) {
            exit;
        }

        $languageList = api_get_languages();
        $hideAll = '';
        foreach ($languageList['all'] as $language) {
            $hideAll .= '$("span:lang('.$language['isocode'].')").filter(
            function() {
                // Ignore ckeditor classes
                return !this.className.match(/cke(.*)/);
            }).hide();';
        }

        $userInfo = api_get_user_info();
        $languageId = api_get_language_id($userInfo['language']);
        $languageInfo = api_get_language_info($languageId);

        echo '
            $(document).ready(function() {
                 '.$hideAll.'                 
                var defaultLanguageFromUser = "'.$languageInfo['isocode'].'";                                
                $("span:lang('.$languageInfo['isocode'].')").filter(
                    function() {
                        // Ignore ckeditor classes
                        return !this.className.match(/cke(.*)/);
                }).show();
                
                var defaultLanguage = "";
                var langFromUserFound = false;
                $(this).find("span").filter(
                    function() {
                        // Ignore ckeditor classes
                        return !this.className.match(/cke(.*)/);
                }).each(function() {
                    defaultLanguage = $(this).attr("lang");                            
                    if (defaultLanguage) {
                        $(this).before().next("br").remove();                
                        if (defaultLanguageFromUser == defaultLanguage) {
                            langFromUserFound = true;
                        }
                    }
                });
                
                // Show default language
                if (langFromUserFound == false && defaultLanguage) {
                    $("span:lang("+defaultLanguage+")").filter(
                    function() {
                            // Ignore ckeditor classes
                            return !this.className.match(/cke(.*)/);
                    }).show();
                }                  

            });
        ';
        break;
    default:
        echo '';
}
exit;
