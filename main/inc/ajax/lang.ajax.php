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
        $languageList = api_get_languages();
        $hideAll = '';
        foreach ($languageList['all'] as $language) {
            $hideAll .= '$("span:lang('.$language['isocode'].')").hide();';
        }

        $userInfo = api_get_user_info();
        $languageId = api_get_language_id($userInfo['language']);
        $languageInfo = api_get_language_info($languageId);

        echo '
            $(document).ready(function() {
                '.$hideAll.'
                var defaultLanguageFromUser = "'.$languageInfo['isocode'].'";
                $("span:lang('.$languageInfo['isocode'].')").show();
                
                var defaultLanguage = "";
                $(this).find("span").each(function() {
                    defaultLanguage = $(this).attr("lang");
                    if (defaultLanguage != "") {
                        return false;
                    }
                });      
                
                if (defaultLanguageFromUser != defaultLanguage) {
                    $("span:lang("+defaultLanguage+")").show();
                }                   
            });
        ';
        break;
    default:
        echo '';
}
exit;
