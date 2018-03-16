<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;

/**
 * Sub language AJAX script to update variables.
 *
 * @package chamilo.admin.sub_language
 */
$this_script = 'sub_language';
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$new_language = Security::remove_XSS($_REQUEST['new_language']);
$language_variable = Security::remove_XSS($_REQUEST['variable_language']);
$file_id = intval($_REQUEST['file_id']);

if (isset($new_language) && isset($language_variable) && isset($file_id)) {
    $file_language = $language_files_to_load[$file_id].'.inc.php';
    $id_language = intval($_REQUEST['id']);
    $sub_language_id = intval($_REQUEST['sub']);
    $all_data_of_language = SubLanguageManager::get_all_information_of_sub_language($id_language, $sub_language_id);

    $path_folder = api_get_path(SYS_LANG_PATH).$all_data_of_language['dokeos_folder'].'/'.$file_language;
    $all_file_of_directory = SubLanguageManager::get_all_language_variable_in_file($path_folder);
    $return_value = SubLanguageManager::add_file_in_language_directory($path_folder);

    //update variable language
    // Replace double quotes to avoid parse errors
    $new_language = str_replace('"', '\"', $new_language);
    // Replace new line signs to avoid parse errors - see #6773
    $new_language = str_replace("\n", "\\n", $new_language);
    $all_file_of_directory[$language_variable] = "\"".$new_language."\";";
    $result_array = [];

    foreach ($all_file_of_directory as $key_value => $value_info) {
        $result_array[$key_value] = SubLanguageManager::write_data_in_file($path_folder, $value_info, $key_value);
    }
    $variables_with_problems = '';
    if (!empty($result_array)) {
        foreach ($result_array as $key => $result) {
            if ($result == false) {
                $variables_with_problems .= $key.' <br />';
            }
        }
    }

    if (isset($_REQUEST['redirect'])) {
        $message = Display::return_message(get_lang('TheNewWordHasBeenAdded'), 'success');

        if (!empty($variables_with_problems)) {
            Display::return_message(
                $path_folder.' '.get_lang('IsNotWritable').'<br /> '.api_ucwords(get_lang('ErrorsFound'))
                    .': <br />'.$variables_with_problems,
                'error'
            );
        }

        Display::addFlash($message);

        if (isset($_REQUEST['extra_field_type'])) {
            $redirectUrl = api_get_path(WEB_CODE_PATH).'admin/extra_fields.php';

            switch ($_REQUEST['extra_field_type']) {
                case ExtraField::USER_FIELD_TYPE:
                    header("Location: {$redirectUrl}?type=user");
                    exit;
                case ExtraField::COURSE_FIELD_TYPE:
                    header("Location: {$redirectUrl}?type=course");
                    exit;
                case ExtraField::SESSION_FIELD_TYPE:
                    header("Location: {$redirectUrl}?type=session");
                    exit;
            }
        }

        if (isset($_REQUEST['skill'])) {
            header('Location: '.api_get_path(WEB_CODE_PATH).'admin/skill_list.php');
            exit;
        }
    }

    if (!empty($variables_with_problems)) {
        echo $path_folder.' '.get_lang('IsNotWritable').'<br /> '.api_ucwords(get_lang('ErrorsFound')).': <br />'.$variables_with_problems;
    } else {
        echo 1;
    }
}
