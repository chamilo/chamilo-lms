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
        header('Content-type: application/x-javascript');

        echo api_get_language_translate_html();
        break;
    case 'translate_portfolio_category':
        if (false === Security::check_token('get')) {
            exit;
        }
        Security::clear_token();
        if (isset($_REQUEST['new_language']) && isset($_REQUEST['variable_language']) && isset($_REQUEST['category_id'])) {
            $newLanguage = Security::remove_XSS($_REQUEST['new_language']);
            $langVariable = Security::remove_XSS($_REQUEST['variable_language']);
            $categoryId = (int) $_REQUEST['category_id'];
            $languageId = (int) $_REQUEST['id'];
            $subLanguageId = (int) $_REQUEST['sub'];

            $langFilesToLoad = SubLanguageManager::get_lang_folder_files_list(
                api_get_path(SYS_LANG_PATH).'english',
                true
            );

            $fileLanguage = $langFilesToLoad[0].'.inc.php';
            $allDataOfLanguage = SubLanguageManager::get_all_information_of_sub_language($languageId, $subLanguageId);

            $pathFolder = api_get_path(SYS_LANG_PATH).$allDataOfLanguage['dokeos_folder'].'/'.$fileLanguage;
            $allFileOfDirectory = SubLanguageManager::get_all_language_variable_in_file($pathFolder);
            $returnValue = SubLanguageManager::add_file_in_language_directory($pathFolder);

            //update variable language
            // Replace double quotes to avoid parse errors
            $newLanguage = str_replace('"', '\"', $newLanguage);
            $newLanguage = str_replace("\n", "\\n", $newLanguage);
            $allFileOfDirectory[$langVariable] = "\"".$newLanguage."\";";

            $resultArray = [];
            foreach ($allFileOfDirectory as $key => $value) {
                $resultArray[$key] = SubLanguageManager::write_data_in_file($pathFolder, $value, $key);
            }

            $variablesWithProblems = '';
            if (!empty($resultArray)) {
                foreach ($resultArray as $key => $result) {
                    if ($result == false) {
                        $variablesWithProblems .= $key.' <br />';
                    }
                }
            }

            if (isset($_REQUEST['redirect'])) {
                $message = Display::return_message(get_lang('TheNewWordHasBeenAdded'), 'success');
                if (!empty($variablesWithProblems)) {
                    $message = Display::return_message(
                        $pathFolder.' '.get_lang('IsNotWritable').'<br /> '.api_ucwords(get_lang('ErrorsFound'))
                        .': <br />'.$variablesWithProblems,
                        'error'
                    );
                }
                Display::addFlash($message);
                header('Location: '.api_get_path(WEB_CODE_PATH).'portfolio/index.php?'.api_get_cidreq().'&action=translate_category&id='.$categoryId.'&sub_language='.$subLanguageId);
                exit;
            }
        }
        break;
    default:
        echo '';
}
exit;
