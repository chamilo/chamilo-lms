<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows for the addition of sub-languages.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

/**
 *        MAIN CODE.
 */
// setting the name of the tool
$tool_name = get_lang('Create sub-language');

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'languages.php', 'name' => get_lang('Chamilo Portal Languages')];

//add data
if (isset($_GET['sub_language_id']) && $_GET['sub_language_id'] == strval(intval($_GET['sub_language_id']))) {
    $language_name = SubLanguageManager::get_name_of_language_by_id($_GET['sub_language_id']);
    if (true === SubLanguageManager::languageExistsById($_GET['sub_language_id'])) {
        $sub_language_id = $_GET['sub_language_id'];
        $sub_language_id_exist = true;
    } else {
        $sub_language_id_exist = false;
    }
}
$language_details = [];
$language_name = '';
if (isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) {
    $language_details = SubLanguageManager::get_all_information_of_language($_GET['id']);
    $language_name = $language_details['original_name'];
    if (true === SubLanguageManager::languageExistsById($_GET['id'])) {
        $parent_id = (int) $_GET['id'];
        $language_id_exist = true;
    } else {
        $language_id_exist = false;
    }
} else {
    $language_id_exist = false;
}

//removed and register

if ((isset($_GET['id']) && $_GET['id'] == strval(intval($_GET['id']))) &&
    (isset($_GET['sub_language_id']) && $_GET['sub_language_id'] == strval(intval($_GET['sub_language_id'])))
) {
    if (true === SubLanguageManager::languageExistsById($_GET['id']) && true === SubLanguageManager::languageExistsById($_GET['sub_language_id'])) {
        $get_all_information = SubLanguageManager::getAllInformationOfSubLanguage((int) $_GET['id'], (int) $_GET['sub_language_id']);
        $original_name = $get_all_information['original_name'];
        $english_name = $get_all_information['english_name'];
        $isocode = $get_all_information['isocode'];
    }
}

$language_name = get_lang('Create sub-languageForLanguage').' ( '.strtolower($language_name).' )';

if (true ===  SubLanguageManager::isParentOfSubLanguage($parent_id) &&
    isset($_GET['action']) && 'deletesublanguage' == $_GET['action']
) {
    $language_name = get_lang('Delete sub-language');
}

$msg = '';

if (isset($_POST['SubmitAddNewLanguage'])) {
    $original_name = $_POST['original_name'];
    $english_name = $_POST['english_name'];
    $isocode = $_POST['isocode'];
    $english_name = str_replace(' ', '_', $english_name);
    $isocode = str_replace(' ', '_', $isocode);

    $sublanguage_available = isset($_POST['sub_language_is_visible']) ? (int) $_POST['sub_language_is_visible'] : 0;
    $check_information = [];
    $check_information = SubLanguageManager::checkIfLanguageExists($original_name, $english_name, $isocode);
    foreach ($check_information as $index_information => $value_information) {
        $allow_insert_info = false;
        if ('original_name' == $index_information) {
            $msg .= Display::return_message(
                get_lang('Already exists').' "'.get_lang('Original name').'" '.'('.$original_name.')',
                'error'
            );
        }
        if ('english_name' == $index_information) {
            $msg .= Display::return_message(
                get_lang('Already exists').' "'.get_lang('English name').'" '.'('.$english_name.')',
                'error'
            );
        }
        if ('isocode' == $index_information) {
            $msg .= Display::return_message(get_lang('This code does not exist').': '.$isocode.'', 'error');
        }
        if ('execute_add' == $index_information && true === $value_information) {
            $allow_insert_info = true;
        }
    }

    if (strlen($original_name) > 0 && strlen($english_name) > 0 && strlen($isocode) > 0) {
        if (true === $allow_insert_info && true === $language_id_exist) {
            $english_name = str_replace(' ', '_', $english_name);
            //Fixes BT#1636
            $english_name = api_strtolower($english_name);

            $firstIso = substr($language_details['isocode'], 0, 2);
            //$english_name = str_starts_with($english_name, $firstIso.'_') ? $english_name : $firstIso.'_'.$english_name;

            $isocode = SubLanguageManager::generateSublanguageCode($firstIso, $_POST['english_name']);
            $str_info = '<br/>'.get_lang('Original name').' : '.$original_name.'<br/>'.get_lang('English name').' : '.$english_name.'<br/>'.get_lang('Character set').' : '.$isocode;

            $mkdir_result = SubLanguageManager::addPoFileForSubLanguage($isocode);
            if ($mkdir_result) {
                $sl_id = SubLanguageManager::addSubLanguage($original_name, $english_name, $sublanguage_available, $parent_id, $isocode);
                if (false === $sl_id) {
                    SubLanguageManager::removePoFileForSubLanguage($isocode);
                    $msg .= Display::return_message(get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'), 'error');
                } else {
                    Display::addFlash(
                        Display::return_message(get_lang('The new sub-language has been added').$str_info, null, false)
                    );
                    api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php?sub_language_id='.$sl_id);
                }
            } else {
                $msg .= Display::return_message(get_lang('The /main/lang directory, used on this portal to store the languages, is not writable. Please contact your platform administrator and report this message.'), 'error');
            }
        } else {
            if (false === $language_id_exist) {
                $msg .= Display::return_message(get_lang('The parent language does not exist.'), 'error');
            }
        }
    } else {
        $msg .= Display::return_message(get_lang('The form contains incorrect or incomplete data. Please check your input.'), 'error');
    }
}

if (isset($_POST['SubmitAddDeleteLanguage'])) {
    $removed = SubLanguageManager::removeSubLanguage($_GET['id'], $_GET['sub_language_id']);
    if ($removed) {
        Display::addFlash(
            Display::return_message(
                get_lang(
                    'The sub language has been removed.'
                )
            )
        );
        api_location(api_get_path(WEB_CODE_PATH).'admin/languages.php');
    }
}

Display:: display_header($language_name);

echo $msg;

if (isset($_GET['action']) && 'definenewsublanguage' == $_GET['action']) {
    $text = $language_name;
    $form = new FormValidator(
        'addsublanguage',
        'post',
        'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&action=definenewsublanguage'
    );
    $class = 'add';
    $form->addElement('header', '', $text);
    $form->addElement('text', 'original_name', get_lang('Original name'), 'class="input_titles"');
    $form->addRule('original_name', get_lang('Required field'), 'required');
    $form->addElement('text', 'english_name', get_lang('English name'), 'class="input_titles"');
    $form->addRule('english_name', get_lang('Required field'), 'required');
    $form->addElement('text', 'isocode', get_lang('ISO code'), 'class="input_titles"');
    $form->addRule('isocode', get_lang('Required field'), 'required');
    $form->addElement('static', null, '&nbsp;', '<i>en, es, fr</i>');
    $form->addElement('checkbox', 'sub_language_is_visible', '', get_lang('Visibility'));
    $form->addButtonCreate(get_lang('Create sub-language'), 'SubmitAddNewLanguage');
    //$values['original_name'] = $language_details['original_name'].'...'; -> cannot be used because of quickform filtering (freeze)
    $values['english_name'] = $language_details['english_name'].'2';
    $values['isocode'] = $language_details['isocode'];
    $form->setDefaults($values);
    $form->display();
} else {
    if (isset($_GET['action']) && 'deletesublanguage' == $_GET['action']) {
        $text = $language_name;
        $form = new FormValidator(
            'deletesublanguage',
            'post',
            'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&sub_language_id='.Security::remove_XSS($_GET['sub_language_id'])
        );
        $class = 'minus';
        $form->addElement('header', '', $text);
        $form->addElement('static', '', get_lang('Original name'), $original_name);
        $form->addElement('static', '', get_lang('English name'), $english_name);
        $form->addElement('static', '', get_lang('Character set'), $isocode);
        $form->addButtonCreate(get_lang('Delete sub-language'), 'SubmitAddDeleteLanguage');
        $form->display();
    }
    if (isset($_GET['action']) && 'definenewsublanguage' == $_GET['action']) {
        echo Display::return_message(get_lang('The sub-language of this language has been added'));
    }
}
/**
 * Footer.
 */
Display:: display_footer();
